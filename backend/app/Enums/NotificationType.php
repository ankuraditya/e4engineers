<?php

namespace App\Enums;

enum NotificationType: string
{
    case AccountCreated = 'account_created';
    case AccountActivation = 'account_activation';
    case PasswordSetup = 'password_setup';
    case EmailVerification = 'email_verification';
    case PasswordReset = 'password_reset';
    case OrderConfirmed = 'order_confirmed';
    case SelfCollectAdminAlert = 'self_collect_admin_alert';
    case SelfCollectReady = 'self_collect_ready';
    case OrderCancelled = 'order_cancelled';
    case PaymentSuccess = 'payment_success';
    case PaymentFailed = 'payment_failed';
    case PaymentPending = 'payment_pending';
    case PaymentRetry = 'payment_retry';
    case InvoiceReady = 'invoice_ready';
    case ShipmentCreated = 'shipment_created';
    case AwbAssigned = 'awb_assigned';
    case PickupScheduled = 'pickup_scheduled';
    case ShipmentPickedUp = 'shipment_picked_up';
    case ShipmentInTransit = 'shipment_in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case DeliveryFailed = 'delivery_failed';
    case RtoInitiated = 'rto_initiated';
    case RtoDelivered = 'rto_delivered';
    case ContactEnquiryReceived = 'contact_enquiry_received';
    case ContactAdminAlert = 'contact_admin_alert';
    case SupportTicketCreated = 'support_ticket_created';
    case SupportAdminAlert = 'support_admin_alert';
    case SupportTicketReply = 'support_ticket_reply';
    case SupportTicketResolved = 'support_ticket_resolved';
    case WorkshopRegistrationReceived = 'workshop_registration_received';
    case WorkshopAdminAlert = 'workshop_admin_alert';
    case CareerApplicationReceived = 'career_application_received';
    case CareerAdminAlert = 'career_admin_alert';
}
