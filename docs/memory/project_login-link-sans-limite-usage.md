# Lien de connexion magique sans `max_uses`

Le lien de connexion sans mot de passe (`login_link` Symfony, module
[[socle.clients]]) est signe et expire au bout de 5 minutes, mais n'a
volontairement PAS de `max_uses` : il reste utilisable plusieurs fois pendant
sa duree de vie.

**Pourquoi** : les antivirus et les previsualiseurs de messagerie (passerelles
de securite, clients mail qui pre-chargent les liens) ouvrent les liens d'un
courriel avant que l'humain ne clique. Avec un lien a usage unique, ce
premier acces automatique le consommerait et l'utilisateur tomberait sur un
lien deja expire sans jamais avoir clique lui-meme.

**Comment l'appliquer** : pour toute fonctionnalite future basee sur un lien a
usage unique envoye par courriel (reinitialisation, invitation, validation
d'adresse...), ne pas supposer qu'un seul usage est consomme par le
destinataire humain. Si un usage unique est reellement necessaire, prevoir
d'abord une verification avec la messagerie reelle des clients (scanners
actives ou non), sinon le meme piege se reproduira silencieusement.
