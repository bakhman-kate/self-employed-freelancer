# Проверка ИНН физического лица

Требуется разработать небольшую веб-форму, на которой будет расположено:
- Текстовое поле для ввода ИНН физического лица
- Кнопка “Отправить”

При отправке веб-формы происходит проверка принадлежности введённого ИНН плательщику налога на профессиональный доход (самозанятый фрилансер). После отправки пользователь получает информацию:
- является ли ИНН самозанятым (в случае успешной проверки)
- код и сообщение об ошибке (если произошла ошибка)

Проверку производить с помощью открытого сервиса ФНС:
https://npd.nalog.ru/html/sites/www.npd.nalog.ru/api_statusnpd_nalog_ru.pdf

Один и тот же ИНН может отправляться на проверку в открытый сервис только один раз в сутки (все повторные запросы брать из базы данных).

Перед отправкой данных в открытый сервис ИНН валидируется в соответствии с алгоритмом:
https://www.egrul.ru/test_inn.html

Т.е. введенный пользователем текст является действительным ИНН физического лица.

## КОНФИГУРАЦИЯ
1. PHP 7
2. Memcached

## УСТАНОВКА
1. git clone https://github.com/bakhman-kate/self-employed-freelancer.git
2. cd self-employed-freelancer
3. composer install