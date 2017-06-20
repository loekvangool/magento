Módulo de integração AnyMarket e Magento
===========================================
---
Versão atual:
---------
**2.18.1**
-----

**(IMPORTANTE) ATUALIZAÇÃO**
========================

> Se há instalado no Magento uma versão anterior a 2.4.x é necessário o
> contato prévio com o suporte, pois as configurações ficarão
> obsoletas, sendo necessário uma reconfiguração, caso contrario
> sua **integração não funcionara corretamente**.
> **Nenhum dado será perdido da versão anterior.**

Descrição
---------
Olá! Com o módulo de integração [AnyMarket] instalado e configurado será possível a integração automática de:

 - Produtos
 - Pedidos
 - Estoque

Instalação
----------
**Fique ligado nas dicas que vão ajudar ter sucesso na sua instalação**

 - Realize um Backup do Magento completo.
 - Certifique-se que não há outros módulos [AnyMarket] instalados em seu sistema.
 - Baixe o repositório como arquivo zip ou faça um fork do projeto.
 - Copie o diretório **app** para dentro do diretório do magento.
 - Force a limpeza do cache **Sistema > Gerenciamento de cache** (System > Cache management)
 - Faça o logof e logue novamente
 - Estará disponível a opção **Sistema > AnyMarket** (System > AnyMarket)
 
Desinstalação
----------
**Deseja remover seu modulo por completo do Magento? Então se ligue nessas dicas**

 - Realize um Backup do Magento completo.
 - Dentro da pasta "uninstall" existe um arquivo chamado "Uninstall_Anymarket.sql" que deve ser executado no banco de dados.
 - O Modulo cria alguns atributos que são uteis para o Anymarket, é necessario a exclusão dos mesmo caso contrario o Cadastro do Produto ficará inacessível.
 - Pelo motivo do modulo utilizar um metodo de pagamento proprio as vendas que forem feitas pelo modulo não estará mais acessivel.
 	Para resolver isso existe um arquivo dentro de "uninstall" com o nome de "Remove_payment_from_orders.sql", ele ira trocar todas as vendas com o Metodo do Anymarket para "Check / Money order", isso fará com que as vendas antigas do Anymarket sejam acessiveis (Use esse script com muita cautela).

Requisitos mínimos
------------------
 - [PHP] 5.4+
 - [Magento] 1.6.x 
  
 
Mais informações ou parcerias
--------
Caso tenha dúvidas, estamos à disposição para atendê-lo no que for preciso: http://www.anymarket.com.br/ ou em nosso [blog].

Desenvolvedores
----
Caso precise de informações sobre a API [AnyMarket] você encontra clicando em: http://developers.anymarket.com.br/
 
Licença
-------
Este código fonte está licenciado sob os termos da **Mozilla Public License, versão 2.0**. Caso não encontre uma cópia distribuida com os arquivos, você pode obter uma em: https://mozilla.org/MPL/2.0/. 

This Source Code Form is subject to the terms of the **Mozilla Public License, v. 2.0**. If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.

Contribuições
-------------
Caso tenha encontrado ou corrigido um bug ou tem alguma fature em mente e queira dividir com a equipe [AnyMarket] ficamos muito gratos e sugerimos os passos a seguir:

 * Faça um fork.
 * Adicione sua feature ou correção de bug.
 * Envie um pull request no [GitHub].

Agradecemos a [Nova PC] pelo desenvolvimento do módulo.  


 [Magento]: https://www.magentocommerce.com/
 [PHP]: http://www.php.net/
 [AnyMarket]: http://www.anymarket.com.br
 [GitHub]: https://github.com/AnyMarket/magento
 [blog]: http://marketplace.anymarket.com.br/
 [Nova PC]: http://www.novapc.com.br/ 
