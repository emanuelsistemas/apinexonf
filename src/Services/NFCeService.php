<?php
namespace NexoNFe\Services;

require_once '../vendor/autoload.php';
require_once '../src/Utils/LogHelper.php';

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NexoNFe\Utils\LogHelper;
use Exception;

class NFCeService
{
    private $make;
    private $tools;
    private $config;
    private $ultimoNumeroGerado;
    
    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->make = new Make();
    }
    
    private function loadConfig()
    {
        return [
            'ambiente' => $_ENV['NFE_AMBIENTE'] ?? 2,
            'uf_emissao' => $_ENV['NFE_UF_EMISSAO'] ?? 'SP',
            'serie_nfce' => $_ENV['NFE_SERIE_NFCE'] ?? 1,
            'timeout' => $_ENV['NFE_TIMEOUT'] ?? 60,
            'valor_maximo' => 5000.00
        ];
    }
    
    public function gerarNFCe($empresa, $consumidor, $produtos, $totais, $pagamentos)
    {
        try {
            LogHelper::info('Iniciando geração de NFC-e', ['empresa_id' => $empresa['id']]);
            
            // Validar dados específicos NFC-e
            $this->validarDadosNFCe($totais, $pagamentos);
            
            // 1. Configurar NFC-e
            $this->configurarNFCe($empresa, $totais);
            
            // 2. Adicionar emitente
            $this->adicionarEmitente($empresa);
            
            // 3. Adicionar consumidor (opcional para NFC-e)
            $this->adicionarConsumidor($consumidor);
            
            // 4. Adicionar produtos
            foreach ($produtos as $index => $produto) {
                $this->adicionarProduto($produto, $index + 1);
            }
            
            // 5. Adicionar totais
            $this->adicionarTotais($totais);
            
            // 6. Adicionar pagamentos (obrigatório NFC-e)
            $this->adicionarPagamentos($pagamentos);
            
            // 7. Gerar XML
            $xml = $this->make->monta();
            $chave = $this->make->getChave();
            
            // 8. Gerar QR Code
            $qrCode = $this->gerarQRCode($chave, $empresa['state'] ?? 'SP', $totais['valor_total']);
            
            // 9. Salvar XML
            $this->salvarXML($xml, $chave);
            
            LogHelper::info('NFC-e gerada com sucesso', ['chave' => $chave]);
            
            return [
                'sucesso' => true,
                'xml' => $xml,
                'chave' => $chave,
                'numero_nfce' => $this->ultimoNumeroGerado,
                'qr_code' => $qrCode,
                'url_consulta' => $this->getUrlConsulta($empresa['state'] ?? 'SP')
            ];
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao gerar NFC-e', ['erro' => $e->getMessage()]);
            
            return [
                'sucesso' => false,
                'erro' => $e->getMessage(),
                'detalhes' => $this->make->getErrors()
            ];
        }
    }
    
    private function validarDadosNFCe($totais, $pagamentos)
    {
        // Validar valor máximo NFC-e
        if ($totais['valor_total'] > $this->config['valor_maximo']) {
            throw new Exception('Valor total não pode exceder R$ 5.000,00 para NFC-e');
        }
        
        // Validar pagamentos obrigatórios
        if (empty($pagamentos) || !is_array($pagamentos)) {
            throw new Exception('Formas de pagamento são obrigatórias para NFC-e');
        }
        
        // Validar soma dos pagamentos
        $totalPagamentos = array_sum(array_column($pagamentos, 'valor'));
        if (abs($totalPagamentos - $totais['valor_total']) > 0.01) {
            throw new Exception('Soma dos pagamentos deve ser igual ao valor total');
        }
    }
    
    private function configurarNFCe($empresa, $totais)
    {
        // Buscar próximo número
        $this->ultimoNumeroGerado = $this->getProximoNumero();
        
        // Tag principal
        $std = new \stdClass();
        $std->versao = '4.00';
        $this->make->taginfNFe($std);
        
        // Identificação NFC-e
        $std = new \stdClass();
        $std->cUF = $this->getCodigoUF($empresa['state'] ?? 'SP');
        $std->cNF = str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        $std->natOp = 'VENDA';
        $std->mod = 65; // NFC-e
        $std->serie = $this->config['serie_nfce'];
        $std->nNF = $this->ultimoNumeroGerado;
        $std->dhEmi = date('Y-m-d\TH:i:sP');
        $std->tpNF = 1; // Saída
        $std->idDest = 1; // Operação interna
        $std->cMunFG = $empresa['codigo_municipio'] ?? 3550308;
        $std->tpImp = 4; // DANFE NFC-e
        $std->tpEmis = 1; // Emissão normal
        $std->cDV = 0; // Calculado automaticamente
        $std->tpAmb = $this->config['ambiente'];
        $std->finNFe = 1; // NFe normal
        $std->indFinal = 1; // Consumidor final (sempre para NFC-e)
        $std->indPres = 1; // Operação presencial (sempre para NFC-e)
        $std->procEmi = 0; // Emissão própria
        $std->verProc = '1.0';
        
        $this->make->tagide($std);
    }
    
    private function adicionarEmitente($empresa)
    {
        // Dados da empresa
        $std = new \stdClass();
        $std->CNPJ = preg_replace('/\D/', '', $empresa['cnpj']);
        $std->xNome = $empresa['name'];
        $std->xFant = $empresa['nome_fantasia'] ?? $empresa['name'];
        $std->IE = $empresa['inscricao_estadual'];
        $std->CRT = $empresa['regime_tributario'] ?? 1;
        $this->make->tagemit($std);
        
        // Endereço da empresa
        $std = new \stdClass();
        $std->xLgr = $empresa['address'];
        $std->nro = $empresa['numero_endereco'] ?? 'S/N';
        $std->xBairro = $empresa['bairro'];
        $std->cMun = $empresa['codigo_municipio'] ?? 3550308;
        $std->xMun = $empresa['city'];
        $std->UF = $empresa['state'];
        $std->CEP = preg_replace('/\D/', '', $empresa['zip_code']);
        $std->cPais = 1058;
        $std->xPais = 'BRASIL';
        $std->fone = preg_replace('/\D/', '', $empresa['phone'] ?? '');
        $this->make->tagenderEmit($std);
    }
    
    private function adicionarConsumidor($consumidor = null)
    {
        if ($consumidor && !empty($consumidor['cpf'])) {
            // Consumidor identificado
            $std = new \stdClass();
            $std->CPF = preg_replace('/\D/', '', $consumidor['cpf']);
            $std->xNome = $consumidor['nome'] ?? 'CONSUMIDOR';
            $std->indIEDest = 9; // Não contribuinte
            $this->make->tagdest($std);
            
            LogHelper::info('Consumidor identificado adicionado', [
                'cpf' => $consumidor['cpf'],
                'nome' => $consumidor['nome'] ?? 'CONSUMIDOR'
            ]);
        } else {
            // Consumidor não identificado (padrão NFC-e)
            LogHelper::info('Consumidor não identificado (padrão NFC-e)');
        }
    }
    
    private function adicionarProduto($produto, $numeroItem)
    {
        // Dados do produto
        $std = new \stdClass();
        $std->item = $numeroItem;
        $std->cProd = $produto['codigo'] ?? str_pad($numeroItem, 6, '0', STR_PAD_LEFT);
        $std->cEAN = $produto['codigo_barras'] ?? 'SEM GTIN';
        $std->xProd = $produto['descricao'];
        $std->NCM = $produto['ncm'] ?? '99999999';
        $std->CFOP = $produto['cfop'] ?? '5102';
        $std->uCom = $produto['unidade'] ?? 'UN';
        $std->qCom = $produto['quantidade'];
        $std->vUnCom = $produto['valor_unitario'];
        $std->vProd = $produto['valor_total'];
        $std->cEANTrib = $produto['codigo_barras'] ?? 'SEM GTIN';
        $std->uTrib = $produto['unidade'] ?? 'UN';
        $std->qTrib = $produto['quantidade'];
        $std->vUnTrib = $produto['valor_unitario'];
        $std->indTot = 1;
        $this->make->tagprod($std);
        
        // Impostos simplificados para NFC-e
        $std = new \stdClass();
        $std->item = $numeroItem;
        $this->make->tagimposto($std);
        
        // ICMS Simples Nacional
        $this->adicionarICMSSimples($produto, $numeroItem);
        
        // PIS
        $this->adicionarPIS($produto, $numeroItem);
        
        // COFINS
        $this->adicionarCOFINS($produto, $numeroItem);
    }
    
    private function adicionarICMSSimples($produto, $numeroItem)
    {
        $std = new \stdClass();
        $std->item = $numeroItem;
        $std->orig = $produto['origem_produto'] ?? 0;
        $std->CSOSN = $produto['csosn_icms'] ?? '102'; // Simples Nacional sem permissão de crédito
        $this->make->tagICMSSN($std);
    }
    
    private function adicionarPIS($produto, $numeroItem)
    {
        $std = new \stdClass();
        $std->item = $numeroItem;
        $std->CST = '01';
        $std->vBC = 0.00;
        $std->pPIS = 0.00;
        $std->vPIS = 0.00;
        $this->make->tagPIS($std);
    }
    
    private function adicionarCOFINS($produto, $numeroItem)
    {
        $std = new \stdClass();
        $std->item = $numeroItem;
        $std->CST = '01';
        $std->vBC = 0.00;
        $std->pCOFINS = 0.00;
        $std->vCOFINS = 0.00;
        $this->make->tagCOFINS($std);
    }
    
    private function adicionarTotais($totais)
    {
        $std = new \stdClass();
        $std->vBC = 0.00;
        $std->vICMS = 0.00;
        $std->vICMSDeson = 0.00;
        $std->vFCP = 0.00;
        $std->vBCST = 0.00;
        $std->vST = 0.00;
        $std->vFCPST = 0.00;
        $std->vFCPSTRet = 0.00;
        $std->vProd = number_format($totais['valor_produtos'], 2, '.', '');
        $std->vFrete = 0.00;
        $std->vSeg = 0.00;
        $std->vDesc = number_format($totais['valor_desconto'] ?? 0, 2, '.', '');
        $std->vII = 0.00;
        $std->vIPI = 0.00;
        $std->vIPIDevol = 0.00;
        $std->vPIS = 0.00;
        $std->vCOFINS = 0.00;
        $std->vOutro = 0.00;
        $std->vNF = number_format($totais['valor_total'], 2, '.', '');
        $this->make->tagICMSTot($std);
    }
    
    private function adicionarPagamentos($pagamentos)
    {
        foreach ($pagamentos as $pagamento) {
            $std = new \stdClass();
            $std->indPag = 0; // Pagamento à vista
            $std->tPag = $pagamento['tipo'];
            $std->vPag = number_format($pagamento['valor'], 2, '.', '');
            
            // Adicionar dados do cartão se necessário
            if (in_array($pagamento['tipo'], ['03', '04'])) { // Cartão
                $std->tpIntegra = 1; // Integrado
                $std->tBand = $pagamento['bandeira'] ?? '99';
                $std->cAut = $pagamento['autorizacao'] ?? null;
            }
            
            $this->make->tagdetPag($std);
        }
    }
    
    public function gerarQRCode($chave, $uf, $valorTotal)
    {
        try {
            $urlConsulta = $this->getUrlConsulta($uf);
            
            // Dados para QR Code NFC-e
            $dadosQR = [
                'chNFe' => $chave,
                'nVersao' => '100',
                'tpAmb' => $this->config['ambiente'],
                'cDest' => '', // CPF do destinatário (vazio se não identificado)
                'dhEmi' => date('Y-m-d\TH:i:sP'),
                'vNF' => number_format($valorTotal, 2, '.', ''),
                'vICMS' => '0.00',
                'digVal' => substr(sha1($chave), 0, 8),
                'cIdToken' => '000001',
                'cHashQRCode' => ''
            ];
            
            // Montar string do QR Code
            $stringQR = $urlConsulta . '?';
            foreach ($dadosQR as $key => $value) {
                if ($value !== '') {
                    $stringQR .= $key . '=' . urlencode($value) . '&';
                }
            }
            $stringQR = rtrim($stringQR, '&');
            
            LogHelper::info('QR Code gerado', ['chave' => $chave]);
            
            return $stringQR;
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao gerar QR Code', ['erro' => $e->getMessage()]);
            throw new Exception('Erro ao gerar QR Code: ' . $e->getMessage());
        }
    }
    
    private function getUrlConsulta($uf)
    {
        $urls = [
            'SP' => [
                'homologacao' => 'https://homologacao.nfce.fazenda.sp.gov.br/NFCeConsultaPublica',
                'producao' => 'https://www.nfce.fazenda.sp.gov.br/NFCeConsultaPublica'
            ]
        ];
        
        $ambiente = $this->config['ambiente'] == 1 ? 'producao' : 'homologacao';
        return $urls[$uf][$ambiente] ?? $urls['SP'][$ambiente];
    }
    
    private function salvarXML($xml, $chave)
    {
        $xmlPath = "../storage/xmls/nfce_{$chave}.xml";
        
        // Criar diretório se não existir
        $dir = dirname($xmlPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($xmlPath, $xml);
        LogHelper::info('XML NFC-e salvo', ['chave' => $chave, 'path' => $xmlPath]);
    }
    
    private function getProximoNumero()
    {
        // Implementar lógica para obter próximo número NFC-e
        // Por enquanto, número fixo para teste
        return 1;
    }
    
    private function getCodigoUF($uf)
    {
        $codigos = [
            'AC' => 12, 'AL' => 17, 'AP' => 16, 'AM' => 23, 'BA' => 29,
            'CE' => 23, 'DF' => 53, 'ES' => 32, 'GO' => 52, 'MA' => 21,
            'MT' => 51, 'MS' => 50, 'MG' => 31, 'PA' => 15, 'PB' => 25,
            'PR' => 41, 'PE' => 26, 'PI' => 22, 'RJ' => 33, 'RN' => 24,
            'RS' => 43, 'RO' => 11, 'RR' => 14, 'SC' => 42, 'SP' => 35,
            'SE' => 28, 'TO' => 17
        ];
        
        return $codigos[$uf] ?? 35; // SP como padrão
    }
}
?>
