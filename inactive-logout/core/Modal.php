<?php

namespace Codemanas\InactiveLogout;

/**
 * Class Modal
 * @package Codemanas\InactiveLogout
 */
class Modal {

	private const DEFAULT_TOAST_MESSAGE = 'You have been automatically logged out due to inactivity';

	public function __construct() {
		add_action( 'wp_footer', array( $this, 'dialog_modal' ), 1 );
		add_action( 'admin_footer', array( $this, 'dialog_modal' ), 1 );
		add_action( 'wp_head', [ $this, 'toastStyles' ] );
		add_action( 'login_footer', [ $this, 'toastContent' ] );
		add_action( 'login_head', [ $this, 'toastStyles' ] );
	}

	/**
	 * Toast CSS
	 *
	 * @return void
	 */
	public function toastStyles() {
		$redirection_logout = $this->showToast();
		if ( ! empty( $redirection_logout ) && ! is_user_logged_in() ) {
			?>
            <style>
                .ina-logout-toast-container {
                    z-index: 99999;
                    position: fixed;
                    width: 400px;
                    box-sizing: border-box;
                    color: #000;
                    top: 20px;
                    right: 20px;
                    padding: 20px;
                    background: #fff;
                    line-height: 26px;
                    border-radius: 6px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1019607843);
                    animation: bounceInRightToastr 0.4s ease both;
                }

                @keyframes bounceInRightToastr {
                    0% {
                        opacity: 0;
                        transform: translateX(300%);
                    }
                    60% {
                        opacity: 1;
                        transform: translateX(-25px);
                    }
                    80% {
                        transform: translateX(10px);
                    }
                    100% {
                        transform: translateX(0);
                    }
                }

                .ina-logout-toast__content {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                    position: relative;
                }

                .ina-logout-toast__content p {
                    margin: 0;
                    font-size: 1rem;
                    color: #545454;
                }

                .ina-logout-toast__content svg {
                    width: 40px;
                    height: 40px;
                }

                .ina-logout-toast__close {
                    position: absolute;
                    right: 5px;
                    top: 5px;
                    background: transparent;
                    border: none;
                    cursor: pointer;
                }
            </style>
			<?php
		}
	}

	public function dialog_modal() {
		if ( is_user_logged_in() ) {
			?>
            <!--START INACTIVE LOGOUT MODAL CONTENT-->
            <div id="ina-logout-modal-container" class="ina-logout-modal-container"></div>
            <!--END INACTIVE LOGOUT MODAL CONTENT-->
			<?php
		} else {
			$this->toastContent();
		}
	}

	public function toastContent() {
		$redirection_logout = $this->showToast();
		if ( ! empty( $redirection_logout ) ) {
			$message = Helpers::getSettings( 'after_redirection_toast_message' );
			$message = ! empty( $message ) ? $message : self::DEFAULT_TOAST_MESSAGE;
			?>
            <div class="ina-logout-toast-container">
                <div aria-live="assertive" role="alert" class="ina-logout-toast__content">
                    <svg viewBox="0 0 24 24" width="100%" height="100%" fill="#f1c40f">
                        <path d="M12 0a12 12 0 1012 12A12.013 12.013 0 0012 0zm.25 5a1.5 1.5 0 11-1.5 1.5 1.5 1.5 0 011.5-1.5zm2.25 13.5h-4a1 1 0 010-2h.75a.25.25 0 00.25-.25v-4.5a.25.25 0 00-.25-.25h-.75a1 1 0 010-2h1a2 2 0 012 2v4.75a.25.25 0 00.25.25h.75a1 1 0 110 2z"></path>
                    </svg>
                    <p><?php esc_html_e( $message ); ?>.</p>
                </div>
                <button class="ina-logout-toast__close" type="button" role="button" aria-label="Close notification">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24">
                        <path d="M 4.7070312 3.2929688 L 3.2929688 4.7070312 L 10.585938 12 L 3.2929688 19.292969 L 4.7070312 20.707031 L 12 13.414062 L 19.292969 20.707031 L 20.707031 19.292969 L 13.414062 12 L 20.707031 4.7070312 L 19.292969 3.2929688 L 12 10.585938 L 4.7070312 3.2929688 z"></path>
                    </svg>
                </button>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const closeBtn = document.querySelector('.ina-logout-toast__close');
                    const container = document.querySelector('.ina-logout-toast-container');
                    if (closeBtn && container) {
                        closeBtn.addEventListener('click', () => container.remove());
                    }
                });
            </script>
			<?php
			delete_transient( 'ina_redirection_logged_out' );
		}
	}

	private function showToast() {
		return get_transient( 'ina_redirection_logged_out' );
	}

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}