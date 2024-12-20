FROM node:20

WORKDIR /app

# Instalar dependências
COPY package*.json ./
RUN npm install

# Copiar os arquivos do projeto
COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host"]
