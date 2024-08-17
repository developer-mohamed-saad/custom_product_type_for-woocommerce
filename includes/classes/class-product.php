<?php

                        defined( 'ABSPATH' ) || exit;

                        class WC_CWPT_Product_Type_product extends WC_Product_Simple {
                            /**
                             * Return the product type
                             * @return string
                             */
                            public function get_type() {
                                return 'product';
                            }
                        
                             
                        }