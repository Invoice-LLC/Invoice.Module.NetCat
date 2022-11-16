<h1>Invoice Payment Module</h1>

<h3>Установка</h3>

1. Скачайте [архив](https://github.com/Invoice-LLC/Invoice.Module.NetCat/archive/master.zip)
2. Распакуйте архив в корневую директорию сайта
3. Выполните файл invoice.sql в бд вашего сайта
5. Перейдите во вкладку **Настройки->Прием платежей** в админ-панели и включите плагин Invoice
![Imgur](https://imgur.com/PrYyjR0.png)

<h3>Настройка</h3>

1. Перейдите во вкладку **Настройки->Прием платежей** в админ-панели и введите API ключ и Merchant Id (Все данные можно получить в [личном кабинете Invoice](https://lk.invoice.su/))

<br>Api ключ и Merchant Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218699-a8f8c00e-7f28-451e-9750-cfa1f43f15d8.png)
![image](https://user-images.githubusercontent.com/91345275/196218722-9c6bb0ae-6e65-4bc4-89b2-d7cb22866865.png)<br>
<br>Terminal Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218998-b17ea8f1-3a59-434b-a854-4e8cd3392824.png)
![image](https://user-images.githubusercontent.com/91345275/196219014-45793474-6dfa-41e3-945d-fc669c916aca.png)<br>

2. Перейдите во вкладку **Настройик->Интернет магазин->Настройки->Оплата** и нажмите "Добавить"
3. Впишите название: "Invocie", затем выберите платежную систему "Invoice"
4. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
      с типом **WebHook** и адресом: **%URL сайта%/netcat/modules/payment/callback.php?paySystem=nc_payment_system_invoice**
   ![Imgur](https://imgur.com/LZEozhf.png)
