# Art Fake Content Creator

Плагин генерации демо контента через терминал WP CLI.
Подходит для разработки, демонстраций, тестирования тем и плагинов.

> Требуется PHP 8.0+, WordPress 6.0+, WP CLI.

## Возможности

- Создание и удаление фейковых записей, страниц, товаров WooCommerce, изображений и таксономий.
- Поддержка профилей: настрой свои наборы данных (названия, описания, изображения).
- Гибкие команды: создавай только нужное, с нужными параметрами.
- Прогресс-бар, статистика и время выполнения.
- Расширяемая архитектура — легко добавлять новые сущности.

## Установка

1. Штатным способом через админку.
    - [Скачиваем релиз](https://github.com/artikus11/art-fake-content-creator/releases/latest/download/art-fake-content-creator.zip)
    - В админке Плагины → Добавить плагин → Кнопка "Загрузить плагин"
2. Клон с GitHub
    ```markdown
    git clone https://github.com/your-repo/art-fake-content-creator.git wp-content/plugins/art-fake-content-creator
    ```
3. Установка с релиза
    ```markdown
   wp plugin install https://github.com/artikus11/art-fake-content-creator/releases/latest/download/art-fake-content-creator.zip --activate
   ```

## Профили

Профили хранятся в папке `wp-content/plugins/art-fake-content-creator/config/`. В подпапках находяться тематические профили (промышленность, мода, одежда, косметика и тд)

```
config/
├── default/
│   ├── titles.json
│   ├── descriptions.json
│   ├── short-descriptions.json
│   ├── images.json
│   └── taxonomies.json
├── cosmetics/
│   └── ... (переопределённые данные)
└── industrial/
    └── ...
```

### Создать свой профиль

Скопируй папку `/config/default/` переименуй. Или
`cp -r config/default config/my-theme-demo`
Отредактируй файлы (titles.json, images.json и др.).

Потом использовать
`wp fcc post create --profile=my-theme-demo --count=5`

## Поддерживаемые сущности

- `product` - Требует Woocommerce. Поддержка simple, variable, атрибутов
- `image` - Загрузка с внешних URL
- `taxonomy` - Поддержка любых такс указанных в профиле
- `attributes` - Требует Woocommerce

## Команды

### Флаги

| Флаг                         | Описание                                                                                                    |
|------------------------------|-------------------------------------------------------------------------------------------------------------|
| **`--profile=<name>`**       | Использовать профиль из `/config/<name>/`                                                                   |
| **`--count=<n>`**            | Количество объектов (где применимо, используется при создании сущностей: товары, записи, пользователи и тд) |
| **`--type=simple,variable`** | Для товаров: тип создания                                                                                   |
| **`--seed`**                 | При создании товаров — создать атрибуты, категории и т.д.                                                   |
| **`--tax=<names>`**          | Указать список таксономий: product_cat,post_tag                                                             |

### Общий формат:

`wp fcc <entity> <action> [options]`

### Создать изображения

```
wp fcc image create --profile=cosmetics
```

### Создать атрибуты

```
wp fcc attribute create --profile=industrial
```

### Создать таксономии

```markdown
# Все таксы из профиля
wp fcc taxonomy create

# Только определённые
wp fcc taxonomy create --tax=product_cat,product_brand
```

### Создать товары WooCommerce

```markdown
# Простые товары
wp fcc product create --count=5 --type=simple

# Вариативные товары
wp fcc product create --count=3 --type=variable --seed

# Вариативные и простые товары 
wp fcc product create --count=3 --type=variable,simple --seed
```

> При указании флага `--seed` при создании товаров будут созданы сопутсвующие сущбности: изображения, атрибуты, таксономии если их еще нет на сайте

### Удаление

```markdown
# Удалить товары
wp fcc product delete --force

# Удалить таксы
wp fcc taxonomy delete --tax=product_cat,product_brand

# Удалить картинки
wp fcc image delete

# Удалить атрибуты
wp fcc attribute delete
```

### Пример: запуск

```markdown
# 1. Создать атрибуты и термины
wp fcc attribute create --profile=cosmetics

# 2. Создать категории и теги
wp fcc taxonomy create --tax=product_cat,product_tag --profile=cosmetics

# 3. Добавить изображения
wp fcc image create --profile=cosmetics

# 4. Создать 10 вариативных и 10 простых товаров
wp fcc product create --count=10 --type=variable,simple --seed --profile=cosmetics
```

> Все фейковые объекты помечаются метой `_created_for_fake_content`.

## Разработка

Плагин построен на модульной архитектуре:

- Команды: `src/CLI/Commands/`
- Менеджеры: `src/Managers/`
- Конфиги: `config/`
- Реестр сущностей: `Services/EntityTypeRegistry.php`

> Через реестр регистрция команды для новой сущности происходит автоматически

### Добавить новую сущность

Реестр сущностей: `Services/EntityTypeRegistry.php` метод `register` добавляем новую сущьность в формате

```php
'mytype'  => [
    'label'     => 'Моя сущность', // название сущности
    'config'    => 'mytypes', // навзание профиля, из которого будут браться данные, если требуется
    'available' => true, // проверка на зависимсоть, если требуется, например нужен какой-нибудь активный плагин
    'service'   => MyTypeManager::class, // обработчика команд
    'command'   => MyTypeCommand::class, // класс команд
],
```

Создаем обработчик команд в папке `src/CLI/Commands`

```php
namespace Art\FakeContent\CLI\Commands;

class MyTypeCommand extends BaseCommand {

	protected function get_entity_type_key(): string {

		return 'mytype';
	}


	protected function get_label_output_create(): string {

		return 'создание новой сущности';
	}


	protected function get_label_output_delete(): string {

		return 'удаление новой сущности';
	}
}
```

Создаем менеджер в папке `src/Services/Managers`

```php
<?php

namespace Art\FakeContent\Services\Managers;

use Art\FakeContent\Utils\QueryUtils;
use Art\FakeContent\Utils\StringUtils;

class MyTypeManager extends BaseManager {

	public function create() {

		// Обработка создания сущности. 
		// С родителя приходит $this->config - данные профиля
	}

        /**
	 * Подсчет количества сущностей для создания.
	 *  Требуется для отображения прогреcc-бара и статистики.
	 * @return void
	 */
	public function prepare_create_operations(): void {
        // обработка количества
        $this->add_create_step( 'mytype', count( $this->config ) );
	}


	public function delete() {
        // Обработка удаления сущности. Удаляются только то, что создано
		// Получаем через мету _created_for_fake_content созданные сущьности
	}

        /**
	 * Подсчет количества сущностей для удаления.
	 *  Требуется для отображения прогреcc-бара и статистики.
	 * @return void
	 */
	public function prepare_delete_operations(): void {
        // обработка количества
        $this->add_delete_step( 'mytype', count( mytype_exists ) );
	}
}
```

> В рамках одной команды методы подсчета количества запускаются раньше, чем обрабка самой команды. См. `src/CLI/Commands/BaseCommand.php`

## Лицензия

GPLv2 или выше. Свободно используй, модифицируй, распространяй.

## Благодарности

Этот плагин вдохновлён и построен на идеях замечательных инструментов и людей:

- WP CLI — за мощную командную строку, которая делает WordPress ещё свободнее.
- WooCommerce — за открытость и гибкость, позволившую легко интегрироваться.
- Unsplash, Pexels, Pixabay — за бесплатные изображения, которые можно использовать в профилях.

А также тебе, кто использует этот плагин. Пусть этот плагин сэкономит тебе часы ручной работы. 💙

### Специальная благодарность

- Дмитрий [@campusboy](https://github.com/campusboy87)  - за идеи, советы и поддержку