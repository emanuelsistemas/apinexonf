# 🧾 API NFe/NFC-e - Nexo PDV

API completa para emissão de Nota Fiscal Eletrônica (NFe) e Nota Fiscal de Consumidor Eletrônica (NFC-e) integrada com SEFAZ.

## 🌐 **Informações do Servidor**

- **Domínio:** `https://apinfe.nexopdv.com`
- **Documentação:** `http://docnexonf.nexopdv.com`
- **Versão:** 1.1.0
- **PHP:** 8.3.6
- **Nginx:** 1.24.0
- **SSL:** Let's Encrypt

## �� **Endpoints Disponíveis**

### 🟢 **Sistema**
- `GET /api/status` - Status da API
- `GET /api/status-sefaz` - Status dos serviços SEFAZ
- `GET /api/logs` - Sistema de logs

### 📄 **NFe (Modelo 55)**
- `POST /api/gerar-nfe` - Gerar NFe
- `POST /api/enviar-sefaz` - Enviar NFe para SEFAZ
- `GET /api/consultar-nfe` - Consultar NFe

### 🧾 **NFC-e (Modelo 65)**
- `POST /api/gerar-nfce` - Gerar NFC-e
- `POST /api/enviar-nfce-sefaz` - Enviar NFC-e para SEFAZ
- `GET /api/consultar-nfce` - Consultar NFC-e
- `POST /api/cancelar-nfce` - Cancelar NFC-e
- `POST /api/gerar-qrcode-nfce` - Gerar QR Code

## 🛠️ **Tecnologias Utilizadas**

- **PHP 8.3** - Linguagem principal
- **NFePHP** - Biblioteca para NFe/NFC-e
- **Supabase** - Banco de dados e certificados
- **Nginx** - Servidor web
- **Let's Encrypt** - Certificados SSL
- **Ubuntu 24.04** - Sistema operacional

## 📦 **Dependências**

```json
{
    "require": {
        "nfephp-org/sped-nfe": "^6.0",
        "nfephp-org/sped-common": "^6.0"
    }
}
```

## ⚙️ **Configuração do Ambiente**

### 1. **Variáveis de Ambiente (.env)**

```env
# Supabase Configuration
SUPABASE_URL=https://xsrirnfwsjeovekwtluz.supabase.co
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# NFe Configuration
NFE_AMBIENTE=2
NFE_UF=SP
NFE_SERIE=1

# NFC-e Configuration
NFCE_AMBIENTE=2
NFCE_UF=SP
NFCE_SERIE=1

# API Configuration
API_VERSION=1.1.0
API_DOMAIN=apinfe.nexopdv.com
```

### 2. **Estrutura de Diretórios**

```
/var/www/nfe-api/
├── public/
│   └── index.php              # Ponto de entrada da API
├── src/
│   ├── Controllers/           # Controllers da API
│   │   └── StatusSefazController.php
│   ├── Services/             # Serviços de negócio
│   │   ├── CertificateService.php
│   │   ├── NFCeService.php
│   │   ├── NFeService.php
│   │   └── StatusSefazService.php
│   ├── Config/               # Configurações
│   └── Utils/                # Utilitários
├── storage/                  # Armazenamento temporário
│   ├── logs/                 # Logs da aplicação
│   └── temp/                 # Arquivos temporários
├── vendor/                   # Dependências Composer
├── composer.json             # Configuração Composer
├── .env                      # Variáveis de ambiente
└── README.md                 # Este arquivo
```

## 🚀 **Instalação Completa**

### **Pré-requisitos**

- Ubuntu 24.04 LTS
- Acesso root ao servidor
- Domínio configurado (apinfe.nexopdv.com)
- Certificado digital A1 (.pfx)

### **1. Atualizar Sistema**

```bash
sudo apt update && sudo apt upgrade -y
```

### **2. Instalar PHP 8.3 e Extensões**

```bash
# Adicionar repositório PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Instalar PHP e extensões necessárias
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-zip php8.3-gd php8.3-mbstring \
    php8.3-curl php8.3-xml php8.3-bcmath php8.3-json \
    php8.3-intl php8.3-soap php8.3-openssl
```

### **3. Instalar Nginx**

```bash
sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx
```

### **4. Instalar Composer**

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### **5. Configurar Diretório da API**

```bash
# Criar diretório
sudo mkdir -p /var/www/nfe-api
cd /var/www/nfe-api

# Clonar repositório
git clone https://github.com/emanuelsistemas/apinexonf.git .

# Instalar dependências
composer install

# Configurar permissões
sudo chown -R www-data:www-data /var/www/nfe-api
sudo chmod -R 755 /var/www/nfe-api
sudo chmod -R 777 /var/www/nfe-api/storage
```

### **6. Configurar Nginx**

```bash
sudo nano /etc/nginx/sites-available/nfe-api
```

**Conteúdo do arquivo:**

```nginx
server {
    server_name apinfe.nexopdv.com;
    root /var/www/nfe-api/public;
    index index.php;

    # CORS Headers
    add_header Access-Control-Allow-Origin "*" always;
    add_header Access-Control-Allow-Methods "GET, POST, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type, Authorization" always;

    # Logs
    access_log /var/log/nginx/nfe-api.access.log;
    error_log /var/log/nginx/nfe-api.error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Bloquear acesso a arquivos sensíveis
    location ~ /\. {
        deny all;
    }

    location ~ /(storage|vendor|src) {
        deny all;
    }

    listen 80;
}
```

**Ativar site:**

```bash
sudo ln -s /etc/nginx/sites-available/nfe-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### **7. Configurar SSL com Let's Encrypt**

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obter certificado SSL
sudo certbot --nginx -d apinfe.nexopdv.com

# Configurar renovação automática
sudo crontab -e
# Adicionar linha:
# 0 12 * * * /usr/bin/certbot renew --quiet
```

### **8. Configurar Variáveis de Ambiente**

```bash
sudo nano /var/www/nfe-api/.env
```

**Adicionar configurações do Supabase e certificados.**

### **9. Testar Instalação**

```bash
# Verificar sintaxe PHP
php -l /var/www/nfe-api/public/index.php

# Testar API
curl https://apinfe.nexopdv.com/api/status

# Verificar logs
sudo tail -f /var/log/nginx/nfe-api.error.log
```

## 🔧 **Configuração do Supabase**

### **Tabelas Necessárias**

1. **certificates** - Armazenamento de certificados digitais
2. **pdv** - Dados das vendas e NFe/NFC-e
3. **logs** - Sistema de logs da API

### **Políticas RLS**

Configurar Row Level Security para proteger dados sensíveis.

## 📝 **Logs e Monitoramento**

### **Localização dos Logs**

- **Nginx Access:** `/var/log/nginx/nfe-api.access.log`
- **Nginx Error:** `/var/log/nginx/nfe-api.error.log`
- **PHP-FPM:** `/var/log/php8.3-fpm.log`
- **API Logs:** `/var/www/nfe-api/storage/logs/`

### **Monitoramento**

```bash
# Verificar status dos serviços
sudo systemctl status nginx php8.3-fpm

# Monitorar logs em tempo real
sudo tail -f /var/log/nginx/nfe-api.error.log

# Verificar uso de recursos
htop
df -h
```

## 🛡️ **Segurança**

### **Firewall**

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### **Backup**

```bash
# Script de backup
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf /backup/nfe-api_$DATE.tar.gz /var/www/nfe-api
```

## 🔄 **Atualizações**

### **Atualizar Código**

```bash
cd /var/www/nfe-api
git pull origin main
composer install --no-dev
sudo systemctl reload php8.3-fpm
```

### **Atualizar Dependências**

```bash
composer update
```

## 🐛 **Troubleshooting**

### **Problemas Comuns**

1. **Erro 500:** Verificar logs do Nginx e PHP-FPM
2. **CORS:** Verificar headers duplicados
3. **Certificado:** Verificar configuração Supabase
4. **Permissões:** Verificar ownership dos arquivos

### **Comandos Úteis**

```bash
# Reiniciar serviços
sudo systemctl restart nginx php8.3-fpm

# Verificar configuração
nginx -t
php -v

# Limpar cache
sudo rm -rf /var/www/nfe-api/storage/temp/*
```

## 📞 **Suporte**

- **Documentação:** http://docnexonf.nexopdv.com
- **GitHub:** https://github.com/emanuelsistemas/apinexonf
- **API Status:** https://apinfe.nexopdv.com/api/status

## 📄 **Licença**

Este projeto é proprietário da Nexo PDV.

---

**🚀 API NFe/NFC-e - Nexo PDV v1.1.0**
