# Модуль ConcordPay для Joomla J2Store

Creator: [ConcordPay](https://concordpay.concord.ua)<br>
Tags: ConcordPay, Joomla, J2Store, payment, payment gateway, credit card, Visa, Masterсard, Apple Pay, Google Pay<br>
Requires at least: Joomla 3.8, J2Store 3.3<br>
License: GNU GPL v3.0<br>
License URI: [License](https://opensource.org/licenses/GPL-3.0)

Этот модуль позволит вам принимать платежи через платёжную систему **ConcordPay**.

Для работы модуля у вас должны быть установлены **CMS Joomla 3.x** и модуль электронной коммерции **J2Store 3.x**.

## Установка

### Установка через загрузку модуля

1. В административной части сайта перейти в *«Расширения -> Расширения -> Загрузить файл пакета»* и загрузить архив с модулем, 
который находится в папке `package`.

2. Перейти в *«Расширения -> Плагины»*, включить плагин *«J2Store - ConcordPay Payment Gateway»*.

3. Перейти в *«J2Store -> Setup -> Payment methods»*, выбрать **«plg_j2store_payment_concordpay»**.

4. Установить необходимые настройки плагина.<br>
   Состояние: **Включено**<br>
   
   Указать данные, полученные от платёжной системы:
    - *Идентификатор продавца (Merchant ID)*;
    - *Секретный ключ (Secret Key)*.

   Также установить статусы заказов на разных этапах их существования.

5. Сохранить настройки модуля.

Модуль готов к работе.

*Модуль Joomla J2Store протестирован для работы с Joomla 3.10.4, J2Store 3.3.19 и PHP 7.2.*