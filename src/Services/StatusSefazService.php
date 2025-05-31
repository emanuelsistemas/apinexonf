<?php

namespace NexoNFe\Services;

use Exception;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NexoNFe\Helpers\LogHelper;

/**
 * Service para consultar status dos serviços da SEFAZ
 */
class StatusSefazService
{
    private $tools;
    private $certificateService;
    
    public function __construct()
    {
        $this->certificateService = new CertificateService();
        $this->initializeTools();
    }
    
    /**
     * Inicializa as ferramentas NFePHP
     */
    private function initializeTools()
    {
        try {
            // Buscar certificado ativo
            $certificateData = $this->certificateService->getCertificateActive();
            
            if (!$certificateData) {
                throw new Exception('Nenhum certificado ativo encontrado');
            }
            
            // Configuração básica
            $config = [
                "atualizacao" => date('Y-m-d H:i:s'),
                "tpAmb" => 2, // Será sobrescrito conforme necessário
                "razaosocial" => "Empresa Teste",
                "cnpj" => "12345678000195",
                "siglaUF" => "SP",
                "schemes" => "PL_009_V4",
                "versao" => '4.00',
                "tokenIBPT" => "",
                "CSC" => "",
                "CSCid" => ""
            ];
            
            // Criar certificado
            $certificate = Certificate::readPfx(
                $certificateData['certificate_content'], 
                $certificateData['password']
            );
            
            // Inicializar Tools
            $this->tools = new Tools(json_encode($config), $certificate);
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao inicializar ferramentas NFePHP para status SEFAZ', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Consulta status do serviço NFe
     */
    public function consultarStatusNFe($uf = 'SP', $ambiente = 2)
    {
        try {
            LogHelper::info('Consultando status NFe SEFAZ', [
                'uf' => $uf,
                'ambiente' => $ambiente
            ]);
            
            // Configurar ambiente
            $this->tools->model->tpAmb = $ambiente;
            
            // Consultar status
            $response = $this->tools->sefazStatus($uf, $ambiente);
            
            if (!$response) {
                throw new Exception('Resposta vazia da SEFAZ');
            }
            
            return $this->processarRespostaStatus($response, 'NFe');
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao consultar status NFe SEFAZ', [
                'uf' => $uf,
                'ambiente' => $ambiente,
                'erro' => $e->getMessage()
            ]);
            
            return [
                'servico' => 'NFe',
                'status' => 'erro',
                'disponivel' => false,
                'erro' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Consulta status do serviço NFC-e
     */
    public function consultarStatusNFCe($uf = 'SP', $ambiente = 2)
    {
        try {
            LogHelper::info('Consultando status NFC-e SEFAZ', [
                'uf' => $uf,
                'ambiente' => $ambiente
            ]);
            
            // Configurar ambiente
            $this->tools->model->tpAmb = $ambiente;
            
            // Para NFC-e, usar o mesmo método mas com configuração específica
            $response = $this->tools->sefazStatus($uf, $ambiente);
            
            if (!$response) {
                throw new Exception('Resposta vazia da SEFAZ');
            }
            
            return $this->processarRespostaStatus($response, 'NFC-e');
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao consultar status NFC-e SEFAZ', [
                'uf' => $uf,
                'ambiente' => $ambiente,
                'erro' => $e->getMessage()
            ]);
            
            return [
                'servico' => 'NFC-e',
                'status' => 'erro',
                'disponivel' => false,
                'erro' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Processa a resposta de status da SEFAZ
     */
    private function processarRespostaStatus($response, $servico)
    {
        try {
            $xml = simplexml_load_string($response);
            
            if (!$xml) {
                throw new Exception('XML de resposta inválido');
            }
            
            // Extrair informações do status
            $cStat = (string)($xml->cStat ?? '');
            $xMotivo = (string)($xml->xMotivo ?? '');
            $dhRecbto = (string)($xml->dhRecbto ?? '');
            $tMed = (string)($xml->tMed ?? '');
            
            // Determinar se o serviço está disponível
            $disponivel = in_array($cStat, ['107', '108']); // Códigos de serviço em operação
            
            $resultado = [
                'servico' => $servico,
                'status' => $cStat,
                'motivo' => $xMotivo,
                'disponivel' => $disponivel,
                'tempo_medio' => $tMed ? (int)$tMed . ' segundos' : null,
                'data_resposta' => $dhRecbto,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Adicionar interpretação do status
            $resultado['interpretacao'] = $this->interpretarStatus($cStat);
            
            LogHelper::info('Status SEFAZ processado', [
                'servico' => $servico,
                'status' => $cStat,
                'disponivel' => $disponivel
            ]);
            
            return $resultado;
            
        } catch (Exception $e) {
            throw new Exception('Erro ao processar resposta de status: ' . $e->getMessage());
        }
    }
    
    /**
     * Interpreta o código de status da SEFAZ
     */
    private function interpretarStatus($cStat)
    {
        $interpretacoes = [
            '107' => 'Serviço em Operação',
            '108' => 'Serviço Paralisado Momentaneamente',
            '109' => 'Serviço Paralisado sem Previsão',
            '110' => 'Serviço em Operação com Restrição',
            '111' => 'Serviço em Operação com Contingência',
            '112' => 'Serviço em Teste'
        ];
        
        return $interpretacoes[$cStat] ?? 'Status Desconhecido';
    }
}
