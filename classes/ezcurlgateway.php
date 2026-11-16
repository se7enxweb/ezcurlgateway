<?php
//
// Definition of eZCurlGateway class
//
// An abstract class for implementing transparent credit card
// payment in eZ publish using cURL.
//
// Created on: <01-Dec-2005 5:00:00 dm>
// Last Updated: December 13, 2005
// Version: 1.0.1
//
// Copyright (C) 2001-2005 Brookins Consulting. All rights reserved.
//
// This source file is part of an extension for the eZ publish (tm)
// Open Source Content Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 (or greater) as published by
// the Free Software Foundation and appearing in the file LICENSE
// included in the packaging of this file.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@brookinsconsulting.com if any conditions
// of this licencing isn't clear to you.
//

/*! \file ezcurlgateway.php
*/

/*!
  \class eZCurlGateway ezcurlgateway.php
  \brief The class eZCurlGateway is a
  base class for payment gateways which
  support payment by executing cURL commands
  to a payment processor over SSL.
*/

// Other extensions ship their own copy of this class (sevenx_payment_stripe guards its
// copy with class_exists), so the constants may already be defined.
defined( 'EZ_CURL_GATEWAY_SHOW_FORM' ) || define( 'EZ_CURL_GATEWAY_SHOW_FORM', 1 );

defined( 'EZ_CURL_GATEWAY_DO_CURL' ) || define( 'EZ_CURL_GATEWAY_DO_CURL', 2 );

// eZPaymentGateway (kernel/shop/classes/ezpaymentgateway.php) is found by the kernel autoload.

class eZCurlGateway extends eZPaymentGateway
{
    // override $useForm as false if you do not need to gather additional
    // user information before you send the curl information (although I can't
    // think of many situations in which this would happen, let me know if you
    // actually use it, otherwise I might just take it out.

    public $useForm = true;

    /*!
     Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /*!
     Signature matches eZPaymentGateway::execute( $process, $event ): objects are
     handles already, and a by-reference parameter would not be compatible with
     the parent (or with subclasses that override it by value) on PHP 8.
    */
    function execute( $process, $event )
    {
        $http = eZHTTPTool::instance();

        $processParameters = $process->attribute( 'parameter_list' );
        $processID         = $process->attribute( 'id' );

        // if a form has been posted, we try and validate it.
        if ( $http->hasPostVariable( 'validate' ) )
        {
            $errors = $this->validateForm( $process );

            if ( !$errors ) {
                $process->setAttribute( 'event_state', EZ_CURL_GATEWAY_DO_CURL );
            }
            else
            {
                return $this->loadForm( $process, $errors );
            }
        }

        if ( $process->attribute( 'event_state' ) == EZ_CURL_GATEWAY_SHOW_FORM )
        {
            // set the event state to do curl if we are not using a form
            if ( !$this->useForm ) {
                $process->setAttribute( 'event_state', EZ_CURL_GATEWAY_DO_CURL );
            }
        }

        switch ( $process->attribute( 'event_state' ) )
        {
            case EZ_CURL_GATEWAY_SHOW_FORM:
            {
                return $this->loadForm( $process );
            }
            break;
            case EZ_CURL_GATEWAY_DO_CURL:
            {
                return $this->doCURL( $process );
            }
            break;
        }
    }

    /*!
    \method abstract
    \brief Needs to be overridden and return
    eZWorkflowEventType::STATUS_FETCH_TEMPLATE_REPEAT if you are using a form.
    */
    function loadForm( &$process, $errors = false )
    {
        return eZWorkflowEventType::STATUS_FETCH_TEMPLATE_REPEAT;
    }

    /*!
    \method abstract
    \brief Should validate post data from the loaded form.
    */
    function validateForm( &$process )
    {
        $errors = array();
        $errors[] = 'You must override this method: ezcurlgateway::validateForm';
        if ( $errors )
            return $this->loadForm( $process, $errors );
        else
            return false;
    }

    /*!
    \method abstract
    \brief Should run the curl command and process the response. Typically you
    would call the loadForm method on a negative response, passing it an error,
    and on a positive response would complete the order by returning
    eZWorkflowEventType::STATUS_ACCEPTED.
    */
    function doCURL( &$process )
    {
        return eZWorkflowEventType::STATUS_ACCEPTED;
    }
}

?>
