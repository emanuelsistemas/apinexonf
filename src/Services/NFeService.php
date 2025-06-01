<?php
namespace NexoNFe\Services;

require_once '../vendor/autoload.php';
require_once '../src/Utils/LogHelper.php';

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NexoNFe\Utils\LogHelper;
use Exception;

class NFeService
{
    private $make;
    private $tools;
    private $config;
    
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
            'serie_nfe' => $_ENV['NFE_SERIE_NFE'] ?? 1,
            'timeout' => $_ENV['NFE_TIMEOUT'] ?? 60
        ];
    }
    
    public function gerarNFe($empresa, $cliente, $produtos, $totais, $pagamentos = [], $numeroNFe = null)
    {
        try {
            LogHelper::info('Iniciando geração de NFe', ['empresa_id' => $empresa['id']]);
            
            // 1. Configurar NFe
            $this->configurarNFe($empresa, $totais);
            
            // 2. Adicionar emitente
            $this->adicionarEmitente($empresa);
            
            // 3. Adicionar destinatário
            $this->adicionarDestinatario($cliente);
            
            // 4. Adicionar produtos
            foreach ($produtos as $index => $produto) {
                $this->adicionarProduto($produto, $index + 1);
            }
            
            // 5. Adicionar totais
            $this->adicionarTotais($totais);
            
            // 6. Adicionar transporte
            $this->adicionarTransporte();
            
            // 7. Adicionar pagamentos
            $this->adicionarPagamentos($pagamentos);
            
            // 8. Gerar XML
            $xml = $this->make->monta();

            // Verificar se houve erros na geração
            if (!$xml) {
                $errors = $this->make->getErrors();
                error_log("Erros NFePHP: " . json_encode($errors));
                throw new Exception("Existem erros nas tags. Detalhes: " . json_encode($errors));
            }

            $chave = $this->make->getChave();
            
            // 9. Salvar XML
            $this->salvarXML($xml, $chave);

            // 10. Gerar PDF (DANFE)
            $pdfPath = $this->gerarPDF($xml, $chave);
            
            LogHelper::info('NFe gerada com sucesso', ['chave' => $chave]);
            
            return [
                'sucesso' => true,
                'xml' => $xml,
                'chave' => $chave,
                'numero_nfe' => $numeroNFe ?? 1,
                'pdf_path' => isset($pdfPath) ? $pdfPath : null
            ];
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao gerar NFe', ['erro' => $e->getMessage()]);
            
            return [
                'sucesso' => false,
                'erro' => $e->getMessage(),
                'detalhes' => $this->make->getErrors()
            ];
        }
    }
    
    private function configurarNFe($empresa, $totais, $numeroNFe = null)
    {
        // Tag principal
        $std = new \stdClass();
        $std->versao = '4.00';
        $this->make->taginfNFe($std);
        
        // Identificação
        $std = new \stdClass();
        $std->cUF = $this->getCodigoUF($empresa['state'] ?? 'SP');
        $std->cNF = str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        $std->natOp = $totais['natureza_operacao'] ?? 'VENDA';
        $std->mod = 55; // NFe
        $std->serie = $this->config['serie_nfe'];
        $std->nNF = $numeroNFe ?? 1;
        $std->dhEmi = date('Y-m-d\TH:i:sP');
        $std->tpNF = 1; // Saída
        $std->idDest = 1; // Operação interna
        $std->cMunFG = $empresa['codigo_municipio'] ?? 3550308;
        $std->tpImp = 1; // DANFE normal
        $std->tpEmis = 1; // Emissão normal
        $std->cDV = 0; // Calculado automaticamente
        $std->tpAmb = $this->config['ambiente'];
        $std->finNFe = 1; // NFe normal
        $std->indFinal = 1; // Consumidor final
        $std->indPres = 1; // Presencial
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
        $std->xBairro = $empresa['bairro'] ?? 'Centro';
        $std->cMun = $empresa['codigo_municipio'] ?? 3550308;
        $std->xMun = $empresa['city'] ?? 'São Paulo';
        $std->UF = $empresa['state'] ?? 'SP';
        $std->CEP = preg_replace('/\D/', '', $empresa['zip_code'] ?? '01234567');
        $std->cPais = 1058;
        $std->xPais = 'BRASIL';
        $std->fone = preg_replace('/\D/', '', $empresa['phone'] ?? '');
        $this->make->tagenderEmit($std);
    }
    
    private function adicionarDestinatario($cliente)
    {
        // Dados do cliente
        $std = new \stdClass();
        
        if (strlen(preg_replace('/\D/', '', $cliente['documento'])) === 11) {
            $std->CPF = preg_replace('/\D/', '', $cliente['documento']);
        } else {
            $std->CNPJ = preg_replace('/\D/', '', $cliente['documento']);
        }
        
        $std->xNome = $cliente['name'];
        $std->indIEDest = 9; // Não contribuinte
        $std->email = $cliente['email'] ?? '';
        $this->make->tagdest($std);
        
        // Endereço do cliente
        $std = new \stdClass();
        $std->xLgr = $cliente['address'] ?? 'Não informado';
        $std->nro = $cliente['numero_endereco'] ?? 'S/N';
        $std->xBairro = $cliente['bairro'] ?? 'Centro';
        $std->cMun = $cliente['codigo_municipio'] ?? 3550308;
        $std->xMun = $cliente['city'] ?? 'São Paulo';
        $std->UF = $cliente['state'] ?? 'SP';
        $std->CEP = preg_replace('/\D/', '', $cliente['zip_code'] ?? '01000000');
        $std->cPais = 1058;
        $std->xPais = 'BRASIL';
        $this->make->tagenderDest($std);
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
        
        // Impostos
        $std = new \stdClass();
        $std->item = $numeroItem;
        $this->make->tagimposto($std);
        
        // ICMS
        $this->adicionarICMS($produto, $numeroItem);
        
        // PIS
        $this->adicionarPIS($produto, $numeroItem);
        
        // COFINS
        $this->adicionarCOFINS($produto, $numeroItem);
    }
    
    private function adicionarICMS($produto, $numeroItem)
    {
        $std = new \stdClass();
        $std->item = $numeroItem;
        $std->orig = $produto['origem_produto'] ?? 0;
        $std->CST = $produto['cst_icms'] ?? '00';
        
        // Para CST 00 (Tributada integralmente)
        if (($produto['cst_icms'] ?? '00') === '00') {
            $std->modBC = 3; // Valor da operação
            $std->vBC = $produto['valor_total'];
            $std->pICMS = $produto['aliquota_icms'] ?? 18;
            $std->vICMS = ($produto['valor_total'] * ($produto['aliquota_icms'] ?? 18)) / 100;
        }
        
        $this->make->tagICMS($std);
    }
    
    private function adicionarPIS($produto, $numeroItem)
    {
        $std = new \stdClass();
        $std->item = $numeroItem;
        $std->CST = $produto['cst_pis'] ?? '01';
        
        if (($produto['cst_pis'] ?? '01') === '01') {
            $std->vBC = $produto['valor_total'];
            $std->pPIS = $produto['aliquota_pis'] ?? 1.65;
            $std->vPIS = ($produto['valor_total'] * ($produto['aliquota_pis'] ?? 1.65)) / 100;
        }
        
        $this->make->tagPIS($std);
    }
    
    private function adicionarCOFINS($produto, $numeroItem)
    {
        $std = new \stdClass();
        $std->item = $numeroItem;
        $std->CST = $produto['cst_cofins'] ?? '01';
        
        if (($produto['cst_cofins'] ?? '01') === '01') {
            $std->vBC = $produto['valor_total'];
            $std->pCOFINS = $produto['aliquota_cofins'] ?? 7.6;
            $std->vCOFINS = ($produto['valor_total'] * ($produto['aliquota_cofins'] ?? 7.6)) / 100;
        }
        
        $this->make->tagCOFINS($std);
    }
    
    private function adicionarTotais($totais)
    {
        $std = new \stdClass();
        // Os valores serão calculados automaticamente pela biblioteca
        $this->make->tagICMSTot($std);
    }
    
    private function adicionarTransporte()
    {
        $std = new \stdClass();
        $std->modFrete = 9; // Sem frete
        $this->make->tagtransp($std);
    }
    
    private function adicionarPagamentos($pagamentos)
    {
        $std = new \stdClass();
        $this->make->tagpag($std);
        
        if (empty($pagamentos)) {
            // Pagamento padrão
            $std = new \stdClass();
            $std->tPag = '01'; // Dinheiro
            $std->vPag = 0.00;
            $this->make->tagdetPag($std);
        } else {
            foreach ($pagamentos as $pagamento) {
                $std = new \stdClass();
                $std->tPag = $pagamento['tipo'] ?? '01';
                $std->vPag = $pagamento['valor'];
                $this->make->tagdetPag($std);
            }
        }
    }
    
    private function salvarXML($xml, $chave)
    {
        $xmlPath = "../storage/xmls/{$chave}.xml";
        
        // Criar diretório se não existir
        $dir = dirname($xmlPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($xmlPath, $xml);
        LogHelper::info('XML salvo', ['chave' => $chave, 'path' => $xmlPath]);
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

    private function gerarPDF($xml, $chave)
    {
        try {
            $pdfPath = "../storage/pdfs/{$chave}.pdf";
            
            // Criar diretório se não existir
            $dir = dirname($pdfPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Gerar DANFE usando sped-da
            $danfe = new \NFePHP\DA\NFe\Danfe($xml);
            $danfe->debugMode(false);
            $danfe->creditsIntegratorFooter('Sistema NFe - Nexo PDV');
            
            // Salvar PDF
            $pdf = $danfe->render();
            file_put_contents($pdfPath, $pdf);
            
            LogHelper::info('PDF gerado', ['chave' => $chave, 'path' => $pdfPath]);
            
            return $pdfPath;
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao gerar PDF', ['erro' => $e->getMessage(), 'chave' => $chave]);
            return null;
        }
    }
