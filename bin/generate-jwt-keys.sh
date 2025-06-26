#!/bin/bash

# Générer la clé privée
openssl genrsa -out config/jwt/private.pem -aes256 4096

# Générer la clé publique
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# Définir les permissions appropriées
chmod 644 config/jwt/public.pem
chmod 600 config/jwt/private.pem 