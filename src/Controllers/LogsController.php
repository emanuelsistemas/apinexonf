<?php
namespace NexoNFe\Controllers;

require_once '../src/Utils/ResponseHelper.php';

use NexoNFe\Utils\ResponseHelper;
use Exception;

class LogsController
{
    private $logPath;
    private $monitorLogPath;
    
    public function __construct()
    {
        $this->logPath = '../storage/logs/nfe.log';
        $this->monitorLogPath = '/var/log/nfe-api-monitor.log';
    }
    
    public function handle()
    {
        try {
            $level = $_GET['level'] ?? 'all'; // all, info, error, debug
            $limit = (int)($_GET['limit'] ?? 100);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $logs = $this->getLogs($this->logPath, $level, $limit, $offset);
            
            ResponseHelper::success([
                'logs' => $logs,
                'total' => count($logs),
                'level_filter' => $level,
                'limit' => $limit,
                'offset' => $offset
            ]);
            
        } catch (Exception $e) {
            error_log("Erro LogsController: " . $e->getMessage());
            ResponseHelper::error('Erro ao buscar logs', 500);
        }
    }
    
    public function handleMonitor()
    {
        try {
            $limit = (int)($_GET['limit'] ?? 50);
            
            $logs = $this->getMonitorLogs($limit);
            
            ResponseHelper::success([
                'logs' => $logs,
                'total' => count($logs),
                'type' => 'monitor'
            ]);
            
        } catch (Exception $e) {
            error_log("Erro LogsController Monitor: " . $e->getMessage());
            ResponseHelper::error('Erro ao buscar logs de monitoramento', 500);
        }
    }
    
    public function handleClear()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $type = $input['type'] ?? 'nfe'; // nfe, monitor, all
            
            $cleared = [];
            
            if ($type === 'nfe' || $type === 'all') {
                if (file_exists($this->logPath)) {
                    file_put_contents($this->logPath, '');
                    $cleared[] = 'NFe logs';
                }
            }
            
            if ($type === 'monitor' || $type === 'all') {
                if (file_exists($this->monitorLogPath)) {
                    file_put_contents($this->monitorLogPath, '');
                    $cleared[] = 'Monitor logs';
                }
            }
            
            ResponseHelper::success([
                'message' => 'Logs limpos com sucesso',
                'cleared' => $cleared,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("Erro LogsController Clear: " . $e->getMessage());
            ResponseHelper::error('Erro ao limpar logs', 500);
        }
    }
    
    private function getLogs($filePath, $level, $limit, $offset)
    {
        if (!file_exists($filePath)) {
            return [];
        }
        
        $content = file_get_contents($filePath);
        if (empty($content)) {
            return [];
        }
        
        $lines = array_filter(explode("\n", $content));
        $logs = [];
        
        foreach ($lines as $line) {
            $logData = json_decode($line, true);
            if ($logData) {
                // Filtrar por level
                if ($level !== 'all' && strtolower($logData['level']) !== strtolower($level)) {
                    continue;
                }
                
                $logs[] = [
                    'timestamp' => $logData['timestamp'],
                    'level' => $logData['level'],
                    'message' => $logData['message'],
                    'context' => $logData['context'] ?? [],
                    'ip' => $logData['ip'] ?? 'unknown'
                ];
            }
        }
        
        // Ordenar por timestamp (mais recente primeiro)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Aplicar offset e limit
        return array_slice($logs, $offset, $limit);
    }
    
    private function getMonitorLogs($limit)
    {
        if (!file_exists($this->monitorLogPath)) {
            return [];
        }
        
        $content = file_get_contents($this->monitorLogPath);
        if (empty($content)) {
            return [];
        }
        
        $lines = array_filter(explode("\n", $content));
        $logs = [];
        
        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\] (.*)/', $line, $matches)) {
                $logs[] = [
                    'timestamp' => $matches[1],
                    'message' => $matches[2],
                    'type' => 'monitor'
                ];
            }
        }
        
        // Pegar os últimos logs
        return array_slice(array_reverse($logs), 0, $limit);
    }
}
?>
