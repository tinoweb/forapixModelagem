FROM nginx:alpine

# Copia os arquivos do frontend para a pasta padrao do nginx
COPY . /usr/share/nginx/html

# Expõe a porta 80 do container
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
