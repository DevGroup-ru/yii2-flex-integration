# Спецификация

**WARNING** Это всё пока черновик и поток сознания.

Описания всех маппингов и в принципе как мы обрабатываем тот 
или иной тип входящей информации хранится в Task.
Т.е., если у нас есть прайс лист в определённом формате - 
мы создаём Task один раз и просто вызываем с другими параметрами 
источника входящей информации(путь к файлу).

Общая логика для импорта:
1. Map input data to abstract entity
    - map input to abstract
2. Reduce input data to abstract entity collection
3. Prioritize and resolve dependencies, apply pre processing for entities(relations attributes -> id), combine searchQueries
4. Map Each abstract entity to our models(find or create)
    - map entity
    - map entity properties
    - repeat for related entities of this entity
5. Reduce - push to db

Общая логика для экспорта:
1. Combine search queries - db reducer
2. Get entities as arrays
3. Map models to abstract entities
    - map entity
    - map entity properties
    - repeat for related entities of this entity
4. Reduce to collections of entities
5. Push collections to output, internal map-reduce
    - map abstract entity to output format
    - map properties and relations of abstract to output
    - reduce to output documents
    - post-process output document(add header/footer)

Преимущества этого подхода - у нас по сути 2 независимых map-reduce таска, 
которые можно будет в последствии распараллелить,
а значит увеличить скорость импорта.

Дополнительное преимущество - промежуточная стадия,
когда у нас есть абстрактные сущности,
можно кешировать и использовать для генерации 
из одного набора данных несколько форматов документов.  

## Функциональные требования

- Работа с любыми сущностями
- Работа с реляциями этих сущностей(привязка)
- Мапперы на входе и на выходе
    - Многомерные:
        - Models[]
        - XML
        - JSON
        - Array[]
    - Двумерные:
        - CSV
        - XLS / XLSX (phpExcel)
- Reducers - делятся на 2 события: beforeMapping & afterMapping
    - All entities - работает на всех сущностях
    - Each entity
    - Each entity relation
- Импорт
    - Конфигурация полей
- Экспорт
    - Конфигурируем, что мы получаем из моделей, с какими реляциями(searchQuery)

Каждый таск import/export представляется в виде json файла с набором конфигурации.


## Итоговый цикл работы

### пример цикла импорта товаров для hexcore/sinful

1. В таске задаём по-умолчаню:
    - что считаем документным ID(в рамках документа идентифицирует продукт например)
    - <language_id, context> по-умолчанию
    - Настройки сущностей и мапперов к ним
2. Открываем документ
    - определяемся с маппингом полей в сущности - читаем заголовок
    - проходимся по каждой строке
    - скачиваем картинки, если они изменились(тут вопрос, что считать изменением)
    - переводим каждую строку в AbstractEntity
3. Reduce в коллекции
    - привязываем детей к родителям
4. Идём по коллекциям булками(searchQuery должен уметь булками)
    - получаем для булки модели, модели в identity map коллекции, ключ - документный ID
    - вместе с моделью сразу получаем with необходимый(перевод например)
    - сопоставляем результат searchQuery с нашей булкой абстрактных моделей, 
      создаём модели, где их нет, устанавливая isNew=true
    - маппим атрибуты, свойства и реляции
    - по прошедствию булки коллекции, если от неё никто не зависит - убираем из identity map, чтобы не жрать память
5. Булками идём по коллекциям
    - в рамках транзакции update/insert