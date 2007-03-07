<?php
/*
  $Id: $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2007 osCommerce

  Released under the GNU General Public License
*/

  require('includes/classes/customers.php');

  class osC_Content_Customers extends osC_Template {

/* Private variables */

    var $_module = 'customers',
        $_page_title = HEADING_TITLE,
        $_page_contents = 'main.php';

/* Class constructor */

    function osC_Content_Customers() {
      global $osC_Database, $osC_MessageStack, $entry_state_has_zones;

      if ( !isset($_GET['action']) ) {
        $_GET['action'] = '';
      }

      if ( !isset($_GET['page']) || ( isset($_GET['page']) && !is_numeric($_GET['page']) ) ) {
        $_GET['page'] = 1;
      }

      if ( !isset($_GET['search']) ) {
        $_GET['search'] = '';
      }

      if ( isset($_GET['cID']) && is_numeric($_GET['cID']) ) {
        $this->_page_title .= ': ' . osc_output_string_protected(osC_Customers_Admin::getData($_GET['cID'], 'customers_full_name'));
      }

      if ( !empty($_GET['action']) ) {
        switch ( $_GET['action'] ) {
          case 'save':
            if ( isset($_GET['cID']) && is_numeric($_GET['cID']) ) {
              $this->_page_contents = 'edit.php';
            } else {
              $this->_page_contents = 'new.php';
            }

            if ( isset($_POST['subaction']) && ($_POST['subaction'] == 'confirm') ) {
              $data = array('gender' => (isset($_POST['gender']) ? $_POST['gender'] : ''),
                            'firstname' => $_POST['firstname'],
                            'lastname' => $_POST['lastname'],
                            'dob_day' => (isset($_POST['dob_days']) ? $_POST['dob_days'] : ''),
                            'dob_month' => (isset($_POST['dob_months']) ? $_POST['dob_months'] : ''),
                            'dob_year' => (isset($_POST['dob_years']) ? $_POST['dob_years'] : ''),
                            'email_address' => $_POST['email_address'],
                            'password' => $_POST['password'],
                            'newsletter' => (isset($_POST['newsletter']) && ($_POST['newsletter'] == 'on') ? '1' : '0'),
                            'status' => (isset($_POST['status']) && ($_POST['status'] == 'on') ? '1' : '0'));

              $error = false;

              if ( ACCOUNT_GENDER > 0 ) {
                if ( ($data['gender'] != 'm') && ($data['gender'] != 'f') ) {
                  $osC_MessageStack->add($this->_module, ENTRY_GENDER_ERROR, 'error');
                  $error = true;
                }
              }

              if ( strlen(trim($data['firstname'])) < ACCOUNT_FIRST_NAME ) {
                $osC_MessageStack->add($this->_module, ENTRY_FIRST_NAME_ERROR, 'error');
                $error = true;
              }

              if ( strlen(trim($data['lastname'])) < ACCOUNT_LAST_NAME ) {
                $osC_MessageStack->add($this->_module, ENTRY_LAST_NAME_ERROR, 'error');
                $error = true;
              }

              if ( ACCOUNT_DATE_OF_BIRTH == '1' ) {
                if ( !checkdate($data['dob_month'], $data['dob_day'], $data['dob_year']) ) {
                  $osC_MessageStack->add($this->_module, ENTRY_DATE_OF_BIRTH_ERROR, 'error');
                  $error = true;
                }
              }

              if ( strlen(trim($data['email_address'])) < ACCOUNT_EMAIL_ADDRESS ) {
                $osC_MessageStack->add($this->_module, ENTRY_EMAIL_ADDRESS_ERROR, 'error');
                $error = true;
              } elseif ( !osc_validate_email_address($data['email_address']) ) {
                $osC_MessageStack->add($this->_module, ENTRY_EMAIL_ADDRESS_CHECK_ERROR, 'error');
                $error = true;
              } else {
                $Qcheck = $osC_Database->query('select customers_id from :table_customers where customers_email_address = :customers_email_address');

                if ( isset($_GET['cID']) && is_numeric($_GET['cID']) ) {
                  $Qcheck->appendQuery('and customers_id != :customers_id');
                  $Qcheck->bindInt(':customers_id', $_GET['cID']);
                }

                $Qcheck->appendQuery('limit 1');
                $Qcheck->bindTable(':table_customers', TABLE_CUSTOMERS);
                $Qcheck->bindValue(':customers_email_address', $data['email_address']);
                $Qcheck->execute();

                if ( $Qcheck->numberOfRows() > 0 ) {
                  $osC_MessageStack->add($this->_module, ENTRY_EMAIL_ADDRESS_ERROR_EXISTS, 'error');
                  $error = true;
                }

                $Qcheck->freeResult();
              }

              if ( ( !isset($_GET['cID']) || !empty($data['password']) ) && (strlen(trim($data['password'])) < ACCOUNT_PASSWORD) ) {
                $osC_MessageStack->add($this->_module, ENTRY_PASSWORD_ERROR, 'error');
                $error = true;
              } elseif ( !empty($_POST['confirmation']) && (trim($data['password']) != trim($_POST['confirmation'])) ) {
                $osC_MessageStack->add($this->_module, ENTRY_PASSWORD_ERROR_NOT_MATCHING, 'error');
                $error = true;
              }

              if ( $error === false ) {
                if ( osC_Customers_Admin::save((isset($_GET['cID']) && is_numeric($_GET['cID']) ? $_GET['cID'] : null), $data) ) {
                  $osC_MessageStack->add_session($this->_module, SUCCESS_DB_ROWS_UPDATED, 'success');
                } else {
                  $osC_MessageStack->add_session($this->_module, ERROR_DB_ROWS_NOT_UPDATED, 'error');
                }

                osc_redirect_admin(osc_href_link_admin(FILENAME_DEFAULT, $this->_module . '&search=' . $_GET['search'] . '&page=' . $_GET['page']));
              }
            }

            break;

          case 'delete':
            $this->_page_contents = 'delete.php';

            if ( isset($_POST['subaction']) && ($_POST['subaction'] == 'confirm') ) {
              if ( osC_Customers_Admin::delete($_GET['cID'], (isset($_POST['delete_reviews']) && ($_POST['delete_reviews'] == 'on') ? true : false)) ) {
                $osC_MessageStack->add_session($this->_module, SUCCESS_DB_ROWS_UPDATED, 'success');
              } else {
                $osC_MessageStack->add_session($this->_module, ERROR_DB_ROWS_NOT_UPDATED, 'error');
              }

              osc_redirect_admin(osc_href_link_admin(FILENAME_DEFAULT, $this->_module . '&search=' . $_GET['search'] . '&page=' . $_GET['page']));
            }

            break;

          case 'saveAddress':
            if ( isset($_GET['abID']) && is_numeric($_GET['abID']) ) {
              $this->_page_contents = 'address_book_edit.php';
            } else {
              $this->_page_contents = 'address_book_new.php';
            }

            if ( isset($_POST['subaction']) && ($_POST['subaction'] == 'confirm') ) {
              $data = array('customer_id' => $_GET['cID'],
                            'gender' => (isset($_POST['ab_gender']) ? $_POST['ab_gender'] : ''),
                            'firstname' => $_POST['ab_firstname'],
                            'lastname' => $_POST['ab_lastname'],
                            'company' => (isset($_POST['ab_company']) ? $_POST['ab_company'] : ''),
                            'street_address' => $_POST['ab_street_address'],
                            'suburb' => (isset($_POST['ab_suburb']) ? $_POST['ab_suburb'] : ''),
                            'postcode' => (isset($_POST['ab_postcode']) ? $_POST['ab_postcode'] : ''),
                            'city' => $_POST['ab_city'],
                            'state' => (isset($_POST['ab_state']) ? $_POST['ab_state'] : ''),
                            'zone_id' => '0', // set below
                            'country_id' => $_POST['ab_country'],
                            'telephone' => (isset($_POST['ab_telephone']) ? $_POST['ab_telephone'] : ''),
                            'fax' => (isset($_POST['ab_fax']) ? $_POST['ab_fax'] : ''),
                            'primary' => (isset($_POST['ab_primary']) && ($_POST['ab_primary'] == 'on') ? true : false));

              $error = false;

              if ( ACCOUNT_GENDER > 0 ) {
                if ( ($data['gender'] != 'm') && ($data['gender'] != 'f') ) {
                  $osC_MessageStack->add($this->_module, ENTRY_GENDER_ERROR, 'error');
                  $error = true;
                }
              }

              if ( strlen(trim($data['firstname'])) < ACCOUNT_FIRST_NAME ) {
                $osC_MessageStack->add($this->_module, ENTRY_FIRST_NAME_ERROR, 'error');
                $error = true;
              }

              if ( strlen(trim($data['lastname'])) < ACCOUNT_LAST_NAME ) {
                $osC_MessageStack->add($this->_module, ENTRY_LAST_NAME_ERROR, 'error');
                $error = true;
              }

              if ( ACCOUNT_COMPANY > 0 ) {
                if ( strlen(trim($data['company'])) < ACCOUNT_COMPANY ) {
                  $osC_MessageStack->add($this->_module, ENTRY_COMPANY_ERROR, 'error');
                  $error = true;
                }
              }

              if ( strlen(trim($data['street_address'])) < ACCOUNT_STREET_ADDRESS ) {
                $osC_MessageStack->add($this->_module, ENTRY_STREET_ADDRESS_ERROR, 'error');
                $error = true;
              }

              if ( ACCOUNT_SUBURB > 0 ) {
                if ( strlen(trim($data['suburb'])) < ACCOUNT_SUBURB ) {
                  $osC_MessageStack->add($this->_module, ENTRY_SUBURB_ERROR, 'error');
                  $error = true;
                }
              }

              if ( ACCOUNT_POST_CODE > 0 ) {
                if ( strlen(trim($data['postcode'])) < ACCOUNT_POST_CODE ) {
                  $osC_MessageStack->add($this->_module, ENTRY_POST_CODE_ERROR, 'error');
                  $error = true;
                }
              }

              if ( strlen(trim($data['city'])) < ACCOUNT_CITY ) {
                $osC_MessageStack->add($this->_module, ENTRY_CITY_ERROR, 'error');
                $error = true;
              }

              if ( ACCOUNT_STATE > 0 ) {
                $Qcheck = $osC_Database->query('select zone_id from :table_zones where zone_country_id = :zone_country_id limit 1');
                $Qcheck->bindTable(':table_zones', TABLE_ZONES);
                $Qcheck->bindInt(':zone_country_id', $data['country_id']);
                $Qcheck->execute();

                $entry_state_has_zones = ( $Qcheck->numberOfRows() > 0 );

                $Qcheck->freeResult();

                if ( $entry_state_has_zones === true ) {
                  $Qzone = $osC_Database->query('select zone_id from :table_zones where zone_country_id = :zone_country_id and zone_code = :zone_code');
                  $Qzone->bindTable(':table_zones', TABLE_ZONES);
                  $Qzone->bindInt(':zone_country_id', $data['country_id']);
                  $Qzone->bindValue(':zone_code', strtoupper($data['state']));
                  $Qzone->execute();

                  if ( $Qzone->numberOfRows() === 1 ) {
                    $data['zone_id'] = $Qzone->valueInt('zone_id');
                  } else {
                    $Qzone = $osC_Database->query('select zone_id from :table_zones where zone_country_id = :zone_country_id and zone_name like :zone_name');
                    $Qzone->bindTable(':table_zones', TABLE_ZONES);
                    $Qzone->bindInt(':zone_country_id', $data['country_id']);
                    $Qzone->bindValue(':zone_name', $data['state'] . '%');
                    $Qzone->execute();

                    if ( $Qzone->numberOfRows() === 1 ) {
                      $data['zone_id'] = $Qzone->valueInt('zone_id');
                    } else {
                      $osC_MessageStack->add($this->_module, ENTRY_STATE_ERROR_SELECT, 'error');
                      $error = true;
                    }
                  }

                  $Qzone->freeResult();
                } else {
                  if ( strlen(trim($data['state'])) < ACCOUNT_STATE ) {
                    $osC_MessageStack->add($this->_module, ENTRY_STATE_ERROR, 'error');
                    $error = true;
                  }
                }
              }

              if ( !is_numeric($data['country_id']) || ($data['country_id'] < 1) ) {
                $osC_MessageStack->add($this->_module, ENTRY_COUNTRY_ERROR, 'error');
                $error = true;
              }

              if ( ACCOUNT_TELEPHONE > 0 ) {
                if ( strlen(trim($data['telephone'])) < ACCOUNT_TELEPHONE ) {
                  $osC_MessageStack->add($this->_module, ENTRY_TELEPHONE_NUMBER_ERROR, 'error');
                  $error = true;
                }
              }

              if ( ACCOUNT_FAX > 0 ) {
                if ( strlen(trim($data['fax'])) < ACCOUNT_FAX ) {
                  $osC_MessageStack->add($this->_module, ENTRY_FAX_NUMBER_ERROR, 'error');
                  $error = true;
                }
              }

              if ( $error === false ) {
                if ( osC_Customers_Admin::saveAddress((isset($_GET['abID']) && is_numeric($_GET['abID']) ? $_GET['abID'] : null), $data) ) {
                  $osC_MessageStack->add_session($this->_module, SUCCESS_DB_ROWS_UPDATED, 'success');
                } else {
                  $osC_MessageStack->add_session($this->_module, ERROR_DB_ROWS_NOT_UPDATED, 'error');
                }

                osc_redirect_admin(osc_href_link_admin(FILENAME_DEFAULT, $this->_module . '&cID=' . $_GET['cID'] . '&search=' . $_GET['search'] . '&page=' . $_GET['page'] . '&action=save&tabIndex=tabAddressBook'));
              }
            }

            break;

          case 'deleteAddress':
            $this->_page_contents = 'address_book_delete.php';

            if ( isset($_POST['subaction']) && ($_POST['subaction'] == 'confirm') ) {
              if ( osC_Customers_Admin::deleteAddress($_GET['abID'], $_GET['cID']) ) {
                $osC_MessageStack->add_session($this->_module, SUCCESS_DB_ROWS_UPDATED, 'success');
              } else {
                $osC_MessageStack->add_session($this->_module, ERROR_DB_ROWS_NOT_UPDATED, 'error');
              }

              osc_redirect_admin(osc_href_link_admin(FILENAME_DEFAULT, $this->_module . '&cID=' . $_GET['cID'] . '&page=' . $_GET['page'] . '&search=' . $_GET['search'] . '&action=save&tabIndex=tabAddressBook'));
            }

            break;

          case 'batchDelete':
            if ( isset($_POST['batch']) && is_array($_POST['batch']) && !empty($_POST['batch']) ) {
              $this->_page_contents = 'batch_delete.php';

              if ( isset($_POST['subaction']) && ($_POST['subaction'] == 'confirm') ) {
                $error = false;

                foreach ($_POST['batch'] as $id) {
                  if ( !osC_Customers_Admin::delete($id, (isset($_POST['delete_reviews']) && ($_POST['delete_reviews'] == 'on') ? true : false)) ) {
                    $error = true;
                    break;
                  }
                }

                if ( $error === false ) {
                  $osC_MessageStack->add_session($this->_module, SUCCESS_DB_ROWS_UPDATED, 'success');
                } else {
                  $osC_MessageStack->add_session($this->_module, ERROR_DB_ROWS_NOT_UPDATED, 'error');
                }

                osc_redirect_admin(osc_href_link_admin(FILENAME_DEFAULT, $this->_module . '&page=' . $_GET['page'] . '&search=' . $_GET['search']));
              }
            }

            break;
        }
      }
    }
  }
?>
