
ЗАПУСК
------
git clone https://github.com/ShishkinP/test-project.git

cd .\test-project\

docker-compose up -d

docker exec -it test-project_app_1 composer install

docker exec -it test-project_app_1 php bin/console orm:schema-tool:update --force

docker exec -it test-project_app_1 php bin/console fetch:trailers

[http://localhost:8080](http://localhost:8080)

Щёлкните знак "палец вверх" для добавления отметки "нравится" 

Что сделано
-----------
MIDDLE-уровень и отметки "нравится".
Истории коммитов почти нет - ваша пометка про них была добавлена сильно после начала выполнения задания. 