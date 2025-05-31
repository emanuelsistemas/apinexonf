<?php
namespace NexoNFe\Utils;

class LogHelper
{
    public static function info($message, $context = [])
    {
        self::log('INFO', $message, $context);
    }
    
    public static function error($message, $context = [])
    {
        self::log('ERROR', $message, $context);
    }
    
    public static function debug($message, $context = [])
    {
        self::log('DEBUG', $message, $context);
    }
    
    private static function log($level, $message, $context)
    {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $logPath = $_ENV['NFE_LOG_PATH'] ?? '../storage/logs/nfe.log';
        
        // Criar diretório se não existir
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($logPath, json_encode($log) . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>
