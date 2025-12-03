# 🛍️ COND - Sistema de Gestão de Condicionais (Mini-ERP)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)

## 📖 Sobre o Projeto

O **COND** é um sistema web desenvolvido para automatizar e gerenciar o processo de vendas por consignação (conhecido como "condicional" ou "sacola") em lojas de vestuário. 

Diferente de sistemas genéricos, o COND foca na integridade do estoque flutuante (produtos que saem da loja mas não foram vendidos) e na análise financeira precisa baseada em **Regime de Caixa**.

O projeto foi construído com foco em **Segurança (Blindagem de Dados)**, **Usabilidade (UX Mobile-First)** e **Performance**.

---

## 🚀 Funcionalidades Principais

### 📦 Gestão de Estoque e Produtos
* **CRUD Completo** com upload de fotos.
* Controle de entrada de mercadorias e fornecedores.
* Cálculo automático de **Margem de Lucro** e preço de custo.
* **Blindagem:** Validação no servidor para impedir estoque ou preços negativos.

### 🛍️ Fluxo de Condicional (Sacola)
* Criação de sacolas com baixa automática de estoque.
* Geração de **Recibo de Impressão** (A4) para assinatura do cliente.
* Processamento de retorno com opções de **Venda** ou **Devolução** (retorno ao estoque).
* Integração com **WhatsApp** para cobrança e envio de detalhes.

### 📊 Inteligência de Negócio (Dashboard)
* **KPIs em Tempo Real:** Lucro (30 dias), Valor na Rua, Top Clientes e Produtos.
* **Gráficos Interativos:** Análise de lucro diário usando Chart.js.
* **Relatórios Avançados:** Relatório de Vendas Detalhado e Balanço de Inventário.

### 🛡️ Segurança e Acesso
* Autenticação segura com senhas criptografadas (`password_hash`).
* **Controle de Nível (RBAC):** Diferenciação entre Administrador e Usuário Padrão.
* Proteção contra CSRF e SQL Injection (uso estrito de PDO Prepared Statements).

### 📱 UX/UI Moderna
* **Layout Responsivo:** Tabelas que se transformam em cards em dispositivos móveis.
* **Sidebar:** Navegação lateral intuitiva.
* **Notificações Toast:** Feedback visual não-intrusivo para todas as ações.
* **Filtros em Tempo Real:** Busca instantânea em listas de clientes e produtos via JavaScript.

---

## 🛠️ Tecnologias Utilizadas

* **Backend:** PHP 8.x (Puro/Vanilla)
* **Banco de Dados:** MySQL / MariaDB
* **Frontend:** HTML5, Tailwind CSS (via CDN)
* **Scripting:** JavaScript (Vanilla), Chart.js, Toastify.js
* **Ícones:** Bootstrap Icons

---

## ⚙️ Como Executar o Projeto

### Pré-requisitos
* Um servidor web local (XAMPP, Laragon, Docker, etc.).
* PHP 8.0 ou superior.
* MySQL.

### Passo a Passo

1.  **Clone o repositório:**
    ```bash
    git clone [https://github.com/seu-usuario/cond-sistema.git](https://github.com/seu-usuario/cond-sistema.git)
    ```

2.  **Configure o Banco de Dados:**
    * Acesse o seu gerenciador (ex: phpMyAdmin).
    * Crie um banco de dados chamado `cond_v1`.
    * Importe o arquivo `database/cond_v1.sql` (ou o SQL fornecido na raiz).

3.  **Configure a Conexão:**
    * Abra o arquivo `conexao.php`.
    * Ajuste as credenciais se necessário:
        ```php
        $host = 'localhost';
        $dbname = 'cond_v1';
        $user = 'root';
        $pass = '';
        ```

4.  **Acesse o Sistema:**
    * Abra o navegador e acesse `http://localhost/cond-sistema`.

### Credenciais Padrão (Teste)
* **Login:** `admin`
* **Senha:** `admin`

---

## 📂 Estrutura de Pastas
---

## 📄 Licença

Este projeto foi desenvolvido para fins acadêmicos e está sob a licença MIT.

---

Desenvolvido por **Roberto Souto Junior** - 4º Período de Sistemas de Informação (2025).
