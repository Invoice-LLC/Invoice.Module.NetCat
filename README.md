<h1>Invoice Payment Module</h1>

<h3>Установка</h3>

1. Скачайте [архив](https://github.com/Invoice-LLC/Invoice.Module.NetCat/archive/master.zip)
2. Распакуйте архив в корневую директорию сайта
3. Выполните файл invoice.sql в бд вашего сайта
5. Перейдите во вкладку **Настройки->Прием платежей** в админ-панели и включите плагин Invoice
![Imgur](https://imgur.com/PrYyjR0.png)

<h3>Настройка</h3>

1. Перейдите во вкладку **Настройки->Прием платежей** в админ-панели и настройте плагин Invoice
2. Перейдите во вкладку **Настройик->Интернет магазин->Настройки->Оплата** и нажмите "Добавить"
3. Впишите название: "Invocie", затем выберите платежную систему "Invoice"
4. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
      с типом **WebHook** и адресом: **%URL сайта%/netcat/modules/payment/callback.php?paySystem=nc_payment_system_invoice**
   ![Imgur](https://imgur.com/LZEozhf.png)
