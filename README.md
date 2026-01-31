# Muon_SMSNotification Module

The `Muon_SMSNotification` module provides SMS notification functionality for Magento 2 using the Twilio service. It is designed to notify store administrators or specific numbers when key events occur in the store, such as order placement or customer registration.

## Features

- **Asynchronous SMS Sending**: Utilizes Magento 2's Message Queue system to ensure that SMS sending doesn't impact checkout performance.
- **Order & Registration Notifications**: Automatically sends SMS alerts for new orders and successful customer registrations.
- **Multi-Store Support**: Fully compatible with multi-store environments, allowing unique settings and credentials per store scope.
- **Retry Logic & Cron Integration**: Robust retry mechanism with dedicated database storage and Cron job for guaranteed delivery.
- **Configurable Message Templates**: Administrators can define SMS templates using dynamic placeholders (e.g., `{{increment_id}}`, `{{customer_name}}`).
- **Flexible Transport & Queue**: Support for multiple transport services (Twilio, Logger) and queue connections (MySQL, RabbitMQ).

## Requirements

- **PHP**: 8.1 or higher.
- **Magento**: 2.4.x
- **Dependencies**: `twilio/sdk` ^8.10

## Installation

1. Install the module via Composer:
   ```bash
   composer require muon/module-sms-notification
   ```
2. Enable the module and update the database:
   ```bash
   bin/magento module:enable Muon_SMSNotification
   bin/magento setup:upgrade
   ```
3. Start the message queue consumer:
   ```bash
   bin/magento queue:consumers:start muon.sms
   ```
4. Ensure Magento Cron is running to process retries:
   ```bash
   bin/magento cron:run
   ```

## Running Tests

The module includes unit and integration tests to ensure code quality and reliability.

### Running Unit Tests:
```bash
cd dev/tests/unit
../../../vendor/bin/phpunit ../../../app/code/Muon/SMSNotification/Test/Unit
```

### Running Integration Tests:
Note: Integration tests require a configured test database.
```bash
cd dev/tests/integration
../../../vendor/bin/phpunit ../../../app/code/Muon/SMSNotification/Test/Integration
```

## Configuration

Navigate to **Stores > Settings > Configuration > Muon Modules > SMS Notification**.

### General Settings
- **Enable SMS Notifications**: Global switch for the feature.
- **Number of Attempts**: Number of retries for failed SMS transmissions.
- **Retry Delay (seconds)**: Delay before attempting to send the SMS again.
- **Queue Connection**: Choose between `Database (Default/Fallback)` or `RabbitMQ (AMQP)`.
- **Send To Phone Number**: The target phone number that receives the notifications.

### Event Notifications
- **Enable Order SMS**: Toggle notifications for new orders.
- **Order Notification Template**: Customize the message for orders.
- **Enable Customer Registration SMS**: Toggle notifications for new customers.
- **Customer Registration Template**: Customize the message for registrations.

### Transport Settings (Twilio)
- **Twilio SID**: Your Twilio Account SID.
- **Twilio Token**: Your Twilio Auth Token.
- **Twilio From Phone Number**: The Twilio number used as the sender.

## Extensibility

### Adding New Event Notifications
To add a new SMS notification for a specific Magento event, follow these steps:

1.  **Identify the Event**: Choose the Magento event you want to trigger the SMS (e.g., `checkout_cart_add_after`).
2.  **Create an Observer**: Create a new observer class in `Observer/` that implements `Magento\Framework\Event\ObserverInterface`. Use `Muon\SMSNotification\Api\NotifierInterface` and `Muon\SMSNotification\Api\MessageBuilderInterface` in the constructor.
3.  **Create a Message Builder**: Implement `Muon\SMSNotification\Api\MessageBuilderInterface` to define how the SMS message should be constructed from the event data.
4.  **Update Configuration**:
    *   Add the new event's enable/disable switch and template field to `etc/adminhtml/system.xml`.
    *   Add default values to `etc/config.xml`.
    *   Add corresponding constants and methods to `Model/Config.php`.
5.  **Register Observer and Builder**:
    *   Register your observer in `etc/events.xml`.
    *   In `etc/di.xml`, configure your observer to use your new message builder.

### Message Builders
Implement `Muon\SMSNotification\Api\MessageBuilderInterface` to customize message content and configure it via `di.xml`.

### Sms Transports
1. Implement `Muon\SMSNotification\Api\SmsTransportInterface`.
2. Add your new transport to the `Muon\SMSNotification\Model\SmsTransportPool` via `di.xml`.
3. Add the transport option to `Muon\SMSNotification\Model\Config\Source\Transport` via `di.xml` so it appears in the configuration.
4. Select your new transport in **General Settings > Transport Service**.

## License

This project is licensed under the MIT License.
