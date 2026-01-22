<?php
/**
 * Disposable email protection.
 *
 * @package Jetstop_Spam
 * @since   1.0.0
 */

namespace Jetstop_Spam\Protection;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disposable class.
 *
 * Blocks temporary/disposable email addresses.
 */
class Disposable {

	/**
	 * List of known disposable email domains.
	 *
	 * @var array
	 */
	private $domains = array(
		// Popular disposable services.
		'10minutemail.com',
		'10minutemail.net',
		'10minutemail.org',
		'20minutemail.com',
		'33mail.com',
		'dispostable.com',
		'dropmail.me',
		'emailondeck.com',
		'fakeinbox.com',
		'fakemailgenerator.com',
		'getairmail.com',
		'getnada.com',
		'guerrillamail.com',
		'guerrillamail.de',
		'guerrillamail.net',
		'guerrillamail.org',
		'guerrillamailblock.com',
		'mailcatch.com',
		'maildrop.cc',
		'mailinator.com',
		'mailinator.net',
		'mailinator2.com',
		'mailnesia.com',
		'mintemail.com',
		'mohmal.com',
		'mytemp.email',
		'sharklasers.com',
		'spamgourmet.com',
		'temp-mail.org',
		'temp-mail.ru',
		'tempail.com',
		'tempinbox.com',
		'tempmail.com',
		'tempmail.net',
		'tempmailaddress.com',
		'tempr.email',
		'tempsky.com',
		'throwaway.email',
		'throwawaymail.com',
		'trashmail.com',
		'trashmail.net',
		'yopmail.com',
		'yopmail.fr',
		'yopmail.net',
		// Additional domains.
		'burnermail.io',
		'discard.email',
		'emailfake.com',
		'emkei.cz',
		'fakemail.fr',
		'fakemail.net',
		'fakemailgenerator.net',
		'grr.la',
		'guerrillamail.biz',
		'harakirimail.com',
		'hmamail.com',
		'inboxalias.com',
		'jetable.org',
		'kasmail.com',
		'mailexpire.com',
		'mailforspam.com',
		'mailin8r.com',
		'mailismagic.com',
		'mailnull.com',
		'mailsac.com',
		'mailscrap.com',
		'mailshell.com',
		'mailslurp.com',
		'mailtemp.info',
		'mailtothis.com',
		'meltmail.com',
		'notmailinator.com',
		'notsharingmy.info',
		'nowmymail.com',
		'otherinbox.com',
		'owlymail.com',
		'proxymail.eu',
		'rcpt.at',
		'rejectmail.com',
		'safetymail.info',
		'sendspamhere.com',
		'shitmail.me',
		'spamavert.com',
		'spambox.us',
		'spamcowboy.com',
		'spamex.com',
		'spamherelots.com',
		'spamhole.com',
		'spamify.com',
		'spaml.com',
		'spammotel.com',
		'spamobox.com',
		'spamspot.com',
		'spamthis.co.uk',
		'superrito.com',
		'suremail.info',
		'teleworm.us',
		'tempemail.co.za',
		'tempemail.com',
		'tempemail.net',
		'tempmaildemo.com',
		'tempmailer.com',
		'tempomail.fr',
		'temporaryemail.net',
		'temporaryinbox.com',
		'thanksnospam.info',
		'throwam.com',
		'tmail.ws',
		'tmailinator.com',
		'trash-mail.at',
		'trash-mail.com',
		'trash-mail.de',
		'trash2009.com',
		'trashymail.com',
		'trashymail.net',
		'trbvm.com',
		'wegwerfemail.de',
		'wegwerfmail.de',
		'wegwerfmail.net',
		'wh4f.org',
		'whyspam.me',
		'willhackforfood.biz',
		'willselfdestruct.com',
		'yopmail.gq',
		'za.com',
		'zoemail.net',
		'zoemail.org',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Allow adding custom domains via filter.
		$this->domains = apply_filters( 'jetstop_disposable_domains', $this->domains );
	}

	/**
	 * Check for disposable email.
	 *
	 * @param array  $data       Submission data.
	 * @param string $ip_address IP address.
	 * @return true|\WP_Error
	 */
	public function check( $data, $ip_address ) {
		$email = $this->extract_email( $data );

		if ( empty( $email ) ) {
			return true;
		}

		if ( $this->is_disposable( $email ) ) {
			return new \WP_Error(
				'disposable_email',
				__( 'Disposable email addresses are not allowed. Please use a permanent email.', 'jetstop-spam' )
			);
		}

		return true;
	}

	/**
	 * Check if email is from a disposable service.
	 *
	 * @param string $email Email address.
	 * @return bool
	 */
	public function is_disposable( $email ) {
		$domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );

		// Direct domain match.
		if ( in_array( $domain, $this->domains, true ) ) {
			return true;
		}

		// Check subdomain (e.g., mail.guerrillamail.com).
		foreach ( $this->domains as $disposable_domain ) {
			if ( substr( $domain, -strlen( $disposable_domain ) - 1 ) === '.' . $disposable_domain ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get list of disposable domains.
	 *
	 * @return array
	 */
	public function get_domains() {
		return $this->domains;
	}

	/**
	 * Extract email from data.
	 *
	 * @param array $data Submission data.
	 * @return string|null
	 */
	private function extract_email( $data ) {
		$email_fields = array( 'email', 'user_email', 'email-1', 'your-email', 'customer_email', 'billing_email' );

		foreach ( $data as $key => $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			$key_lower = strtolower( $key );

			if ( strpos( $key_lower, 'email' ) !== false || in_array( $key_lower, $email_fields, true ) ) {
				$email = sanitize_email( $value );
				if ( is_email( $email ) ) {
					return $email;
				}
			}
		}

		return null;
	}
}
