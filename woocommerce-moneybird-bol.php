<?php
/*
Plugin Name: MoneyBird API integration [extra Bol.com settings add-on]
Plugin URI: https://extensiontree.com/nl/producten/woocommerce-extensies/moneybird-api-koppeling/
Version: 1.7.1
Author: Marco Cox, <a href="https://extensiontree.com/nl/">ExtensionTree.com</a>
Description: Adds extra options to the MoneyBird plugin for bol.com orders. Works with the Bol.com integrations from Woosa and Channable.
Requires at least: 3.8
Tested up to: 6.5
WC requires at least: 2.2.0
WC tested up to: 9.1
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

require 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

PucFactory::buildUpdateChecker(
    'https://github.com/extensiontree/woocommerce-moneybird-bol/',
    __FILE__,
    'woocommerce-moneybird-bol'
);

// Declare HPOS compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});


if (is_plugin_active('woocommerce/woocommerce.php')) {

    function insert_woocommerce_moneybird_bol_integration($integrations)
    {
        if (in_array('WC_MoneyBird2', $integrations)) {
            $integrations[array_search('WC_MoneyBird2', $integrations)] = 'WC_MoneyBird_Bol';
        }

        return $integrations;
    }

    add_filter('woocommerce_integrations', 'insert_woocommerce_moneybird_bol_integration', 20);


    function woocommerce_moneybird_bol_init()
    {
        require_once('class-wc-moneybird-bol.php');
    }

    add_action('plugins_loaded', 'woocommerce_moneybird_bol_init', 20);


    function is_bol_order($order)
    {
        if ($order->get_meta('created_via', true) == 'bol.com') {
            return true;
        } elseif ($order->get_meta('_created_via', true) == 'bol.com') {
            return true;
        } elseif ($order->get_meta('payment_method_title', true) == 'Bol.com') {
            return true;
        } elseif ($order->get_meta('_payment_method_title', true) == 'Bol.com') {
            return true;
        }

        // Check parent in case of refund
        $parent_order_id = $order->get_parent_id();
        $parent_order = null;
        if ($parent_order_id) {
            $parent_order = wc_get_order($parent_order_id);
        }
        if ($parent_order) {
            if ($parent_order->get_meta('created_via', true) == 'bol.com') {
                return true;
            } elseif ($parent_order->get_meta('_created_via', true) == 'bol.com') {
                return true;
            } elseif ($parent_order->get_meta('payment_method_title', true) == 'Bol.com') {
                return true;
            } elseif ($parent_order->get_meta('_payment_method_title', true) == 'Bol.com') {
                return true;
            }
        }

        return false;
    }


    function wc_mb_bol_modify_invoice($invoice, $order)
    {
        if (!is_bol_order($order)) {
            return $invoice;
        }

        $mb = WC()->integrations->integrations['moneybird2'];

        // Maybe suppress invoicing
        if (isset($mb->settings['bol_invoice_enabled']) && ($mb->settings['bol_invoice_enabled'] != 'yes')) {
            return array();
        }

        // Change workflow?
        if (isset($mb->settings['bol_workflow_id'])) {
            $workflow_id = $mb->settings['bol_workflow_id'];
            if (!empty($workflow_id)) {
                if ($workflow_id != 'auto') {
                    $invoice['workflow_id'] = $workflow_id;
                } else {
                    if (isset($invoice['workflow_id'])) {
                        unset($invoice['workflow_id']);
                    }
                }
            }
        }

        // Change document style?
        if (isset($mb->settings['bol_document_style_id'])) {
            if (!empty($mb->settings['bol_document_style_id'])) {
                $invoice['document_style_id'] = $mb->settings['bol_document_style_id'];
            }
        }

        // Change revenue ledger account?
        if (isset($mb->settings['bol_revenue_ledger_account_id'])) {
            if (!empty($mb->settings['bol_revenue_ledger_account_id'])) {
                for ($i = 0; $i < count($invoice['details_attributes']); $i++) {
                    $invoice['details_attributes'][$i]['ledger_account_id'] = substr($mb->settings['bol_revenue_ledger_account_id'], 1);
                }
            }
        }

        return $invoice;
    }

    add_filter('woocommerce_moneybird_invoice', 'wc_mb_bol_modify_invoice', 10, 2);
    add_filter('woocommerce_moneybird_credit_invoice', 'wc_mb_bol_modify_invoice', 10, 2);


    function wc_mb_bol_modify_register_payment($register_payment, $order)
    {
        if (!is_bol_order($order)) {
            return $register_payment;
        }

        $mb = WC()->integrations->integrations['moneybird2'];

        if (isset($mb->settings['bol_always_mark_paid'])) {
            if ($mb->settings['bol_always_mark_paid'] == 'yes') {
                $register_payment = true;
            }
        }

        return $register_payment;
    }

    add_filter('woocommerce_moneybird_register_payment', 'wc_mb_bol_modify_register_payment', 10, 2);


    function wc_mb_bol_modify_order_is_paid($order_is_paid, $order)
    {
        if (!is_bol_order($order)) {
            return $order_is_paid;
        }

        $mb = WC()->integrations->integrations['moneybird2'];

        if (isset($mb->settings['bol_always_mark_paid'])) {
            if ($mb->settings['bol_always_mark_paid'] == 'yes') {
                $order_is_paid = true;
            }
        }

        return $order_is_paid;
    }

    add_filter('woocommerce_moneybird_is_order_paid', 'wc_mb_bol_modify_order_is_paid', 10, 2);


    function wc_mb_bol_modify_sendmode($sendmode, $order)
    {
        if (!is_bol_order($order)) {
            return $sendmode;
        }

        $mb = WC()->integrations->integrations['moneybird2'];

        if (isset($mb->settings['bol_never_send'])) {
            if ($mb->settings['bol_never_send'] == 'yes') {
                if ($sendmode != 'draft') {
                    $sendmode = 'Manual';
                }
            }
        }

        return $sendmode;
    }

    add_filter('woocommerce_moneybird_sendmode', 'wc_mb_bol_modify_sendmode', 10, 2);


    function wc_mb_bol_modify_invoice_reference($reference, $order)
    {
        if (!is_bol_order($order)) {
            return $reference;
        }

        $mb = WC()->integrations->integrations['moneybird2'];

        if (isset($mb->settings['bol_invoice_reference'])) {
            $reference = $mb->settings['bol_invoice_reference'];
        }

        return $reference;
    }

    add_filter('woocommerce_moneybird_reference', 'wc_mb_bol_modify_invoice_reference', 10, 2);
} // if woocommerce active
