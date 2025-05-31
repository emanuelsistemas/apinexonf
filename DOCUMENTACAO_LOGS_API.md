# 📊 Sistema de Logs - API NFe/NFC-e

## 🎯 **Sistema de Logs Implementado**

A API NFe/NFC-e possui um **sistema completo de logs** para debug e monitoramento.

---

## 📋 **Tipos de Logs Disponíveis**

### **1. Logs da API NFe/NFC-e:**
- **Localização:** `/var/www/nfe-api/storage/logs/nfe.log`
- **Formato:** JSON estruturado
- **Níveis:** INFO, ERROR, DEBUG
- **Conteúdo:** Operações da API, erros, debug

### **2. Logs de Monitoramento:**
- **Localização:** `/var/log/nfe-api-monitor.log`
- **Formato:** Texto simples
- **Conteúdo:** Status da API, reinicializações, alertas

---

## 🌐 **Endpoints de Logs para Frontend**

### **1. Buscar Logs da API**
```http
GET /api/logs
```

**Parâmetros Query:**
- `level` - Filtro por nível (all, info, error, debug) - Padrão: all
- `limit` - Quantidade de logs (1-1000) - Padrão: 100
- `offset` - Offset para paginação - Padrão: 0

**Exemplo:**
```bash
curl "https://apinfe.nexopdv.com/api/logs?level=error&limit=10" \
-H "Authorization: Bearer nfe_api_token_2025"
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "logs": [
      {
        "timestamp": "2025-05-31 10:00:00",
        "level": "ERROR",
        "message": "Erro ao gerar NFe",
        "context": {
          "empresa_id": 1,
          "erro": "Certificado não encontrado"
        },
        "ip": "192.168.1.100"
      }
    ],
    "total": 1,
    "level_filter": "error",
    "limit": 10,
    "offset": 0
  }
}
```

### **2. Logs de Monitoramento**
```http
GET /api/logs/monitor
```

**Parâmetros Query:**
- `limit` - Quantidade de logs (1-500) - Padrão: 50

**Exemplo:**
```bash
curl "https://apinfe.nexopdv.com/api/logs/monitor?limit=5" \
-H "Authorization: Bearer nfe_api_token_2025"
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "logs": [
      {
        "timestamp": "2025-05-31 10:00:02",
        "message": "API funcionando corretamente (HTTP 200)",
        "type": "monitor"
      },
      {
        "timestamp": "2025-05-31 09:55:01", 
        "message": "API funcionando corretamente (HTTP 200)",
        "type": "monitor"
      }
    ],
    "total": 2,
    "type": "monitor"
  }
}
```

### **3. Limpar Logs**
```http
POST /api/logs/clear
```

**Payload:**
```json
{
  "type": "nfe"  // nfe, monitor, all
}
```

**Exemplo:**
```bash
curl -X POST "https://apinfe.nexopdv.com/api/logs/clear" \
-H "Content-Type: application/json" \
-H "Authorization: Bearer nfe_api_token_2025" \
-d '{"type": "nfe"}'
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "message": "Logs limpos com sucesso",
    "cleared": ["NFe logs"],
    "timestamp": "2025-05-31 10:05:00"
  }
}
```

---

## 💻 **Implementação Frontend**

### **Componente de Logs (React):**
```javascript
import React, { useState, useEffect } from 'react';

const LogsViewer = () => {
  const [logs, setLogs] = useState([]);
  const [level, setLevel] = useState('all');
  const [loading, setLoading] = useState(false);

  const fetchLogs = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `https://apinfe.nexopdv.com/api/logs?level=${level}&limit=50`,
        {
          headers: {
            'Authorization': 'Bearer nfe_api_token_2025'
          }
        }
      );
      
      const result = await response.json();
      
      if (result.success) {
        setLogs(result.data.logs);
      }
    } catch (error) {
      console.error('Erro ao buscar logs:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLogs();
    
    // Auto-refresh a cada 30 segundos
    const interval = setInterval(fetchLogs, 30000);
    return () => clearInterval(interval);
  }, [level]);

  const clearLogs = async () => {
    try {
      await fetch('https://apinfe.nexopdv.com/api/logs/clear', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer nfe_api_token_2025'
        },
        body: JSON.stringify({ type: 'nfe' })
      });
      
      fetchLogs(); // Recarregar logs
    } catch (error) {
      console.error('Erro ao limpar logs:', error);
    }
  };

  return (
    <div className="logs-viewer">
      <div className="logs-header">
        <h3>Logs da API NFe/NFC-e</h3>
        
        <div className="logs-controls">
          <select value={level} onChange={(e) => setLevel(e.target.value)}>
            <option value="all">Todos</option>
            <option value="info">Info</option>
            <option value="error">Erro</option>
            <option value="debug">Debug</option>
          </select>
          
          <button onClick={fetchLogs} disabled={loading}>
            {loading ? 'Carregando...' : 'Atualizar'}
          </button>
          
          <button onClick={clearLogs} className="btn-danger">
            Limpar Logs
          </button>
        </div>
      </div>

      <div className="logs-content">
        {logs.map((log, index) => (
          <div key={index} className={`log-entry log-${log.level.toLowerCase()}`}>
            <div className="log-timestamp">{log.timestamp}</div>
            <div className="log-level">{log.level}</div>
            <div className="log-message">{log.message}</div>
            {log.context && Object.keys(log.context).length > 0 && (
              <div className="log-context">
                <pre>{JSON.stringify(log.context, null, 2)}</pre>
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default LogsViewer;
```

### **CSS para Logs:**
```css
.logs-viewer {
  max-width: 1200px;
  margin: 20px auto;
  padding: 20px;
  background: #f5f5f5;
  border-radius: 8px;
}

.logs-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.logs-controls {
  display: flex;
  gap: 10px;
}

.logs-content {
  max-height: 600px;
  overflow-y: auto;
  background: white;
  border-radius: 4px;
  padding: 10px;
}

.log-entry {
  padding: 10px;
  margin-bottom: 10px;
  border-left: 4px solid #ccc;
  background: #fafafa;
}

.log-entry.log-info {
  border-left-color: #2196F3;
}

.log-entry.log-error {
  border-left-color: #f44336;
  background: #ffebee;
}

.log-entry.log-debug {
  border-left-color: #ff9800;
}

.log-timestamp {
  font-size: 12px;
  color: #666;
  margin-bottom: 5px;
}

.log-level {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: bold;
  margin-bottom: 5px;
}

.log-level {
  background: #e0e0e0;
  color: #333;
}

.log-message {
  font-weight: 500;
  margin-bottom: 5px;
}

.log-context {
  background: #f0f0f0;
  padding: 8px;
  border-radius: 4px;
  font-size: 12px;
}

.log-context pre {
  margin: 0;
  white-space: pre-wrap;
}
```

---

## 📊 **Dashboard de Monitoramento**

### **Métricas Disponíveis:**
```javascript
const fetchMetrics = async () => {
  // Logs de erro nas últimas 24h
  const errors = await fetch('/api/logs?level=error&limit=1000');
  
  // Status de monitoramento
  const monitor = await fetch('/api/logs/monitor?limit=100');
  
  // Status atual da API
  const status = await fetch('/api/status');
  
  return {
    errorCount: errors.data.logs.length,
    lastCheck: monitor.data.logs[0]?.timestamp,
    apiStatus: status.status
  };
};
```

---

## 🔧 **Configurações de Log**

### **Níveis de Log:**
- **INFO:** Operações normais (geração NFe, consultas)
- **ERROR:** Erros críticos (falhas na SEFAZ, certificados)
- **DEBUG:** Informações detalhadas (payloads, respostas)

### **Rotação de Logs:**
- **Automática:** Configurada via logrotate
- **Retenção:** 30 dias para logs da API
- **Compressão:** Logs antigos são comprimidos

### **Monitoramento:**
- **Frequência:** A cada 5 minutos
- **Auto-restart:** Se API falhar 3 vezes consecutivas
- **Alertas:** Registrados nos logs de monitoramento

---

## �� **Alertas e Notificações**

### **Tipos de Alertas:**
1. **API Offline** - Registrado no monitor
2. **Erro de Certificado** - Log ERROR
3. **Falha SEFAZ** - Log ERROR
4. **Timeout** - Log ERROR

### **Implementar Notificações:**
```javascript
// Verificar logs de erro periodicamente
const checkForErrors = async () => {
  const response = await fetch('/api/logs?level=error&limit=10');
  const errors = response.data.logs;
  
  // Filtrar erros recentes (últimos 5 minutos)
  const recentErrors = errors.filter(log => {
    const logTime = new Date(log.timestamp);
    const now = new Date();
    return (now - logTime) < 5 * 60 * 1000; // 5 minutos
  });
  
  if (recentErrors.length > 0) {
    // Enviar notificação
    showNotification('Erro na API NFe', recentErrors[0].message);
  }
};

// Executar a cada 1 minuto
setInterval(checkForErrors, 60000);
```

---

## 🎯 **Resumo dos Endpoints**

| Endpoint | Método | Descrição | Auth |
|----------|--------|-----------|------|
| `/api/logs` | GET | Buscar logs da API | ✅ |
| `/api/logs/monitor` | GET | Logs de monitoramento | ✅ |
| `/api/logs/clear` | POST | Limpar logs | ✅ |

### **Autenticação:**
```
Authorization: Bearer nfe_api_token_2025
```

---

## ✅ **Status do Sistema de Logs**

- ✅ **Logs estruturados** em JSON
- ✅ **Múltiplos níveis** (INFO, ERROR, DEBUG)
- ✅ **Endpoints para frontend** implementados
- ✅ **Monitoramento automático** ativo
- ✅ **Rotação automática** configurada
- ✅ **Filtros e paginação** disponíveis
- ✅ **Limpeza de logs** via API

**🚀 Sistema de logs completo e pronto para uso no frontend!**
