build:
	docker-compose up --build -d

bash:
	docker-compose exec php bash

up:
	docker-compose up -d