# 🛍️ COND — Sistema de Gestão de Condicionais (Mini-ERP)

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-blue?style=for-the-badge)

> Sistema web para automatizar e gerenciar vendas por **consignação** (sacolas) em lojas de vestuário, com foco em integridade de estoque flutuante e análise financeira em **regime de caixa**.

---

## ✨ Destaques técnicos

- 🛡️ **Segurança:** PDO Prepared Statements, `password_hash`, controle CSRF, RBAC (admin/usuário)
- 📊 **BI integrado:** KPIs em tempo real, lucro diário, top clientes/produtos via Chart.js
- 📱 **Mobile-first:** tabelas viram cards no mobile, sidebar adaptativa, toasts não-intrusivos
- 💬 **Integração WhatsApp:** envio de cobranças e detalhes da sacola
- 🧾 **Recibo A4** para impressão e assinatura do cliente

---

## 🚀 Funcionalidades

### 📦 Estoque & Produtos
- CRUD com upload de fotos
- Cálculo automático de margem de lucro
- Validação server-side contra estoque/preço negativo

### 🛍️ Fluxo de Condicional
- Criação de sacola com baixa automática de estoque
- Retorno: venda parcial/total ou devolução ao estoque
- Geração de recibo imprimível (A4)

### 📊 Dashboard & Relatórios
- KPIs: lucro 30 dias, valor na rua, top clientes, top produtos
- Gráficos interativos (lucro diário)
- Relatórios de vendas detalhadas e balanço de inventário

### 🛡️ Segurança
- Autenticação com `password_hash`
- RBAC (Administrador / Usuário Padrão)
- Proteção CSRF + SQL Injection (PDO)

---

## 🛠️ Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.x (Vanilla) |
| Banco | MySQL / MariaDB |
| Frontend | HTML5 + Tailwind CSS |
| Scripts | JavaScript Vanilla, Chart.js, Toastify.js |
| Ícones | Bootstrap Icons |

---

## ⚙️ Como executar

### Pré-requisitos
- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Servidor web local (XAMPP, Laragon, Docker, etc.)

### Passo a passo

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/RobertoSoutoJr/COND_V2.git
   cd COND_V2
   ```

2. **Configure as variáveis de ambiente:**
   ```bash
   cp .env.example .env
   ```
   Edite o `.env` com as credenciais do seu MySQL:
   ```env
   DB_HOST=localhost
   DB_NAME=cond_v1
   DB_USER=seu_usuario
   DB_PASS=sua_senha
   ```

3. **Importe o banco de dados:**
   - Crie o banco `cond_v1`
   - Importe `database/cond_v1.sql`

4. **Acesse o sistema:**
   - `http://localhost/COND_V2`
   - Crie um usuário pela tela de registro

---

## 📂 Estrutura

```
COND_V2/
├── api_*.php              # Endpoints AJAX (lucro, sacolas)
├── auth_*.php             # Autenticação e checks de sessão
├── clientes_*.php         # CRUD de clientes
├── condicionais_*.php     # Fluxo principal (criar, baixar, listar, imprimir)
├── entradas_*.php         # Entradas de mercadoria
├── produtos_*.php         # CRUD de produtos
├── relatorio_*.php        # Relatórios (vendas, inventário, top clientes)
├── usuarios_*.php         # Gestão de usuários
├── conexao.php            # PDO via .env
├── menu.php               # Sidebar reusável
├── css/, js/, img/        # Assets
└── database/              # Schema SQL
```

---

## 📄 Licença

MIT — veja [LICENSE](LICENSE).

---

Desenvolvido por **Roberto Souto Jr** — 4º Período de Sistemas de Informação (UNIPAM, 2025)
