<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_MoneyBird_Bol extends WC_MoneyBird2 {

    function init_form_fields() {
        parent::init_form_fields();

        if (!isset($this->form_fields['workflow_id'])) {
            return;
        }

        // Add extra form fields for payment-specific styles & workflows.
        $this->form_fields['bol'] = array(
            'title'       => 'Instellingen voor bol.com bestellingen',
            'type'        => 'title',
            'description' => '',
        );
        $this->form_fields['bol_invoice_enabled'] = array(
            'title'             => 'Facturen voor bol.com bestellingen',
            'description'       => 'Maak MoneyBird facturen voor bol.com bestellingen.',
            'type'              => 'select',
            'options'           => array('yes' => 'Ja', 'no' => 'Nee'),
            'default'           => 'yes'
        );
        $this->form_fields['bol_workflow_id'] = array(
            'title'             => 'Workflow bol.com bestellingen',
            'type'              => 'select',
            'options'           => $this->form_fields['workflow_id']['options']
        );
        $this->form_fields['bol_document_style_id'] = array(
            'title'             => 'Huisstijl bol.com bestellingen',
            'type'              => 'select',
            'options'           => $this->form_fields['document_style_id']['options']
        );
        $this->form_fields['bol_revenue_ledger_account_id'] = array(
            'title'             => 'Omzetcategorie bol.com bestellingen',
            'type'              => 'select',
            'description'       => 'Overrule omzetcategorie voor alle factuurregels voor bol.com bestellingen.',
            'options'           => $this->form_fields['products_ledger_account_id']['options']
        );
        $this->form_fields['bol_always_mark_paid'] = array(
            'title'             => 'Altijd als betaald markeren',
            'label'             => 'Markeer facturen voor bol.com bestellingen altijd als betaald.',
            'type'              => 'checkbox',
        );
        $this->form_fields['bol_never_send'] = array(
            'title'             => 'Factuur niet verzenden',
            'label'             => 'Verstuur facturen voor bol.com bestellingen nooit naar de klant.',
            'type'              => 'checkbox',
        );
    }
}