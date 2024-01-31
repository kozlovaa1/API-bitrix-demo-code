# API-bitrix-demo-code
Примеры API запросов для Битрикс с использованием Swagger через phpDoc

**Используются инфоблоки:**

Города - code `cities`

Каталог - code `catalog`

Отзывы - code `reviews`

**API-запросы:**

`/api/review/add` - добавление отзыва (ФИО, оценка, плюсы, минусы, фото) с уведомлением по email

`/api/review/like` - лайк отзыву

`/api/review/dislike` - дизлайк отзыву

`/api/geo/setCity` - установка текущего города и переход на поддомен города

`/api/geo/findCities` - поиск городов по текстовому запросу из инфоблока Города
