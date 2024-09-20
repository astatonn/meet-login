Requisitos do servidor:
PHP ^7.2 (de preferencia 7)
Composer ^2.0
Node ^18.12
NPM compativel com o node
Apache
PHP Precisa ter a extensão LDAP instalada
------------------------------------------------
Rodar o sistema:

Composer install
npm install
npm run prod

----------------------------------------------------------------------
Trocar os campos no arquivo .env:
APP_URL=sua url
LDAP_HOST=ldap://seuip:suaporta
JITSI_APP_SECRET=o ID do seu secret do jitsi (é obtido no servidor do jitsi)
JITSI_ASAP_ACCEPETED_AUDIENCES=audiences do //normalmente esse é igual ao ID
JITSI_ASAP_ACCEPTED_ISSUERS=issuers //normalmente é igual ao ID
JITSI_URL=url do seu jitsi

------------------------------------------------------------------------
Após alterar algo no FRONT (imagem texto etc) é preciso rodar o comando npm run prod

-------------------------------------------------------------------------
No zimlet "com_zimbra_pensomeet_cal" é preciso alterar a propriedade "PensoMeetCalURL" no arquivo
config_template.xml e colocar a URL do seu meet

no zimlet "com_zimbra_new_pensomeet" é preciso alterar a propriedade "pensoMeetLink" no arquivo
config_template.xml e colocar a URL do seu meet
