# Email template variables

Templates accept only the variables shown in their `available_variables` API field.

- Order confirmed: `customer_name`, `order_number`, `order_total`, `payment_method`, `order_url`
- Password setup: `customer_name`, `setup_url`
- Payment success: `order_number`, `amount_paid`, `gateway`, `payment_reference`, `order_url`, `invoice_url`
- Payment failed: `order_number`, `retry_url`
- Invoice ready: `order_number`, `invoice_url`
- Shipment created: `order_number`, `courier_name`, `tracking_url`
- AWB assigned: `order_number`, `courier_name`, `tracking_number`, `tracking_url`
- Pickup scheduled: `order_number`
- Picked up: `order_number`, `courier_name`, `tracking_url`
- In transit: `order_number`, `tracking_url`
- Out for delivery: `order_number`
- Delivered: `order_number`
- Delivery failed: `order_number`, `tracking_url`

Placeholders use `{{variable_name}}`. PHP, Blade directives, expressions, and arbitrary variables are unsupported.
