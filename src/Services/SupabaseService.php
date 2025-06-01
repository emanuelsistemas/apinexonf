<?php
namespace NexoNFe\Services;

require_once '../src/Utils/LogHelper.php';

use NexoNFe\Utils\LogHelper;
use Exception;

class SupabaseService
{
    private $supabaseUrl;
    private $supabaseKey;
    
    public function __construct()
    {
        $this->supabaseUrl = $_ENV['SUPABASE_URL'];
        $this->supabaseKey = $_ENV['SUPABASE_KEY'];
        
        if (!$this->supabaseUrl || !$this->supabaseKey) {
            throw new Exception('Configurações do Supabase não encontradas');
        }
    }
    
    public function buscarCertificadoEmpresa($empresaId)
    {
        try {
            $url = $this->supabaseUrl . '/rest/v1/empresas?id=eq.' . $empresaId . '&select=certificado_digital_path,certificado_digital_senha,certificado_digital_validade,certificado_digital_status';
            
            $headers = [
                'Authorization: Bearer ' . $this->supabaseKey,
                'apikey: ' . $this->supabaseKey
            ];
            
            $response = $this->makeRequest('GET', $url, null, $headers);
            
            if (empty($response)) {
                throw new Exception('Empresa não encontrada');
            }
            
            $empresa = $response[0];
            
            if (empty($empresa['certificado_digital_path'])) {
                throw new Exception('Certificado digital não configurado para esta empresa');
            }
            
            if ($empresa['certificado_digital_status'] !== 'ativo') {
                throw new Exception('Certificado digital inativo ou expirado');
            }
            
            LogHelper::info('Dados do certificado obtidos', ['empresa_id' => $empresaId]);
            
            return $empresa;
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao buscar certificado da empresa', [
                'empresa_id' => $empresaId,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function baixarCertificado($certificadoPath)
    {
        try {
            $url = $this->supabaseUrl . '/storage/v1/object/certificadodigital/' . urlencode($certificadoPath);
            
            $headers = [
                'Authorization: Bearer ' . $this->supabaseKey
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $certificadoData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception('Erro cURL ao baixar certificado: ' . $error);
            }
            
            if ($httpCode !== 200) {
                throw new Exception('Erro HTTP ao baixar certificado: ' . $httpCode);
            }
            
            if (empty($certificadoData)) {
                throw new Exception('Certificado vazio ou não encontrado');
            }
            
            // Salvar temporariamente
            $tempPath = '../storage/certificados/temp/' . uniqid() . '.pfx';
            
            // Criar diretório se não existir
            $dir = dirname($tempPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($tempPath, $certificadoData);
            
            LogHelper::info('Certificado baixado temporariamente', ['temp_path' => $tempPath]);
            
            return $tempPath;
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao baixar certificado', [
                'certificado_path' => $certificadoPath,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function deletarCertificadoTemporario($tempPath)
    {
        try {
            if (file_exists($tempPath)) {
                unlink($tempPath);
                LogHelper::info('Certificado temporário deletado', ['temp_path' => $tempPath]);
            }
        } catch (Exception $e) {
            LogHelper::error('Erro ao deletar certificado temporário', [
                'temp_path' => $tempPath,
                'erro' => $e->getMessage()
            ]);
        }
    }
    
    public function salvarNFe($dados)
    {
        try {
            $url = $this->supabaseUrl . '/rest/v1/pdv';
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->supabaseKey,
                'apikey: ' . $this->supabaseKey
            ];
            
            $response = $this->makeRequest('POST', $url, $dados, $headers);
            
            LogHelper::info('NFe salva no Supabase', ['id' => $response['id'] ?? null]);
            
            return $response;
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao salvar NFe no Supabase', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }
    
    public function atualizarStatusNFe($id, $status, $dadosAdicionais = [])
    {
        try {
            $url = $this->supabaseUrl . '/rest/v1/pdv?id=eq.' . $id;
            
            $dados = array_merge([
                'status_nfe' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ], $dadosAdicionais);
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->supabaseKey,
                'apikey: ' . $this->supabaseKey
            ];
            
            $response = $this->makeRequest('PATCH', $url, $dados, $headers);
            
            LogHelper::info('Status NFe atualizado no Supabase', [
                'id' => $id,
                'status' => $status
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            LogHelper::error('Erro ao atualizar status NFe no Supabase', [
                'id' => $id,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function makeRequest($method, $url, $data = null, $headers = [])
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($data && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro cURL: ' . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception('Erro HTTP: ' . $httpCode . ' - ' . $response);
        }
        
        return json_decode($response, true);
    }
}
?>
