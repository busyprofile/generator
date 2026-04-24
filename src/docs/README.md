## Инструкция по установке
- Написана для человека знающего Laravel и VueJS

### Стек 
- Laravel ^11.9
- Vue ^3.4 (Composition)
- InertiaJS ^1.0

> Используются очереди через QUEUE_CONNECTION=database, запускаются через supervisor.

> Докер не используется.

### Требования
- Сервер: Ubuntu 22-24
- База данных: Mysql 8, на PostgreSQL не тестировался, но вероятнее всего будет работать. Так как используются только базовые/простые параметры таблиц и запросы.
- PHP 8.3
  - Расширения, кроме базовых используются: ext-bcmath, ext-gmp.
- node v22.2.0 и выше.
- npm 10.7.0 и выше.

### Установка
- Команды:
  - php artisan config:cache
  - php artisan route:cache
  - php artisan app:install - кастомная команда для установки проекта. Сама запускает миграции и другие необходимые команды. Как работает можно посмотреть в InstallAppCommand.
  - npm run build
- Очереди - используется базовая инструкция из документации ларавель.
  - https://laravel.com/docs/11.x/queues#supervisor-configuration
  - команда: php artisan queue:work --queue="default,callback,notification"
  - можно по желанию установить Redis, Horizon и использовать QUEUE_CONNECTION=redis

### Кастомные параметры в .env
- TELEGRAM_BOT_NAME
- TELEGRAM_BOT_TOKEN
- TELEGRAM_REDIRECT_URI=https://example.com/auth/telegram/callback
  - используется для редиректа после авторизации в разделе уведомлений
- TELEGRAM_WEBHOOK_TOKEN=token
  - секретный токен, нужен чтобы убедиться что уведомления приходят от телеграм, а не ктото шлет их ручками.
  - https://example.com/telegram-bot/{token}/webhook
