# 📋 API NFe/NFC-e - Documentação Completa

## 🌐 Base URL
```
https://apinfe.nexopdv.com
```

## 🔐 Autenticação
```
Authorization: Bearer {API_TOKEN}
```

---

## 📊 Endpoints Disponíveis

### 🟢 Status da API
```http
GET /api/status
```

**Resposta:**
```json
{
  "status": "API NFe/NFC-e Online",
  "timestamp": "2025-05-30 13:45:28",
  "version": "1.1.0",
  "php_version": "8.3.6",
  "domain": "apinfe.nexopdv.com",
  "modelos_suportados": {
    "NFe": "Modelo 55 - Nota Fiscal Eletrônica",
    "NFC-e": "Modelo 65 - Nota Fiscal de Consumidor Eletrônica"
  }
}
```

---

## 📄 NFe (Modelo 55) - Nota Fiscal Eletrônica

### 1. Gerar NFe
```http
POST /api/gerar-nfe
```

**Payload:**
```json
{
  "empresa": {
    "id": 1,
    "cnpj": "12.345.678/0001-95",
    "name": "Empresa Teste",
    "inscricao_estadual": "123456789",
    "address": "Rua Teste, 123",
    "city": "São Paulo",
    "state": "SP",
    "zip_code": "01000-000"
  },
  "cliente": {
    "documento": "123.456.789-00",
    "name": "Cliente Teste",
    "address": "Rua Cliente, 456",
    "city": "São Paulo",
    "state": "SP"
  },
  "produtos": [
    {
      "codigo": "001",
      "descricao": "Produto Teste",
      "quantidade": 1,
      "valor_unitario": 100.00,
      "valor_total": 100.00,
      "ncm": "12345678",
      "cfop": "5102"
    }
  ],
  "totais": {
    "valor_produtos": 100.00,
    "valor_total": 100.00,
    "natureza_operacao": "VENDA"
  }
}
```

### 2. Enviar NFe para SEFAZ
```http
POST /api/enviar-sefaz
```

### 3. Consultar NFe
```http
GET /api/consultar-nfe?chave={chave}&empresa_id={id}
```

---

## 🧾 NFC-e (Modelo 65) - Nota Fiscal de Consumidor Eletrônica

### 1. Gerar NFC-e
```http
POST /api/gerar-nfce
```

**Payload:**
```json
{
  "empresa": {
    "id": 1,
    "cnpj": "12.345.678/0001-95",
    "name": "Empresa Teste",
    "inscricao_estadual": "123456789",
    "address": "Rua Teste, 123",
    "city": "São Paulo",
    "state": "SP",
    "zip_code": "01000-000"
  },
  "consumidor": {
    "cpf": "123.456.789-00",
    "nome": "Consumidor Teste"
  },
  "produtos": [
    {
      "codigo": "001",
      "descricao": "Produto Teste",
      "quantidade": 1,
      "valor_unitario": 50.00,
      "valor_total": 50.00,
      "ncm": "12345678",
      "cfop": "5102"
    }
  ],
  "totais": {
    "valor_produtos": 50.00,
    "valor_total": 50.00
  },
  "pagamentos": [
    {
      "tipo": "01",
      "valor": 50.00
    }
  ]
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "xml": "<?xml version=\"1.0\"...",
    "chave": "35250512345678000195650010000000011234567890",
    "numero_nfce": 1,
    "qr_code": "https://homologacao.nfce.fazenda.sp.gov.br/NFCeConsultaPublica?chNFe=...",
    "url_consulta": "https://homologacao.nfce.fazenda.sp.gov.br/NFCeConsultaPublica"
  }
}
```

### 2. Enviar NFC-e para SEFAZ
```http
POST /api/enviar-nfce-sefaz
```

### 3. Consultar NFC-e
```http
GET /api/consultar-nfce?chave={chave}&empresa_id={id}
```

### 4. Cancelar NFC-e
```http
POST /api/cancelar-nfce
```

**Payload:**
```json
{
  "chave": "35250512345678000195650010000000011234567890",
  "justificativa": "Cancelamento por erro de digitação",
  "empresa_id": 1
}
```

### 5. Gerar QR Code
```http
POST /api/gerar-qrcode-nfce
```

### 6. Configurações NFC-e
```http
GET /api/config-nfce
```

**Resposta:**
```json
{
  "valor_maximo": 5000,
  "tipos_pagamento": {
    "01": "Dinheiro",
    "02": "Cheque",
    "03": "Cartão de Crédito",
    "04": "Cartão de Débito",
    "05": "Crédito Loja",
    "10": "Vale Alimentação",
    "11": "Vale Refeição",
    "12": "Vale Presente",
    "13": "Vale Combustível",
    "15": "Boleto Bancário",
    "99": "Outros"
  },
  "ambiente_padrao": 2,
  "serie_padrao": 1,
  "consumidor_obrigatorio": false
}
```

---

## 🔍 Diferenças NFe vs NFC-e

| Aspecto | NFe (55) | NFC-e (65) |
|---------|----------|------------|
| **Destinatário** | Obrigatório | Opcional |
| **Valor Limite** | Sem limite | R$ 5.000,00 |
| **Pagamentos** | Opcional | **Obrigatório** |
| **QR Code** | Não | **Obrigatório** |
| **Finalidade** | B2B | B2C |

---

## 📁 Estrutura de Arquivos

```
/var/www/nfe-api/
├── public/
│   └── index.php (Router principal)
├── src/
│   ├── Controllers/
│   │   ├── GerarNFeController.php
│   │   ├── GerarNFCeController.php
│   │   ├── EnviarSefazController.php
│   │   ├── EnviarNFCeSefazController.php
│   │   ├── ConsultarNFeController.php
│   │   ├── ConsultarNFCeController.php
│   │   ├── CancelarNFCeController.php
│   │   └── GerarQRCodeNFCeController.php
│   ├── Services/
│   │   ├── NFeService.php
│   │   ├── NFCeService.php
│   │   └── SefazService.php
│   └── Utils/
│       ├── ResponseHelper.php
│       └── LogHelper.php
├── storage/
│   ├── certificados/
│   ├── xmls/
│   └── logs/
└── vendor/ (NFePHP)
```

---

## 🚀 Status da Implementação

✅ **Concluído:**
- Router principal com suporte NFe e NFC-e
- Controllers separados para cada funcionalidade
- Services específicos (NFe e NFC-e)
- Validações específicas para cada modelo
- Sistema de logs estruturado
- Geração de QR Code para NFC-e
- SSL/HTTPS configurado

⏳ **Pendente:**
- Integração com certificados do Supabase
- Comunicação real com SEFAZ
- Numeração sequencial automática
- Testes com dados reais

---

**API Online:** https://apinfe.nexopdv.com 🚀
