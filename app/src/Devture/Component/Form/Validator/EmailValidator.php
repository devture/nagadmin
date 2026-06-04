<?php
namespace Devture\Component\Form\Validator;

class EmailValidator {

	/**
	 * Checks whether the specified email looks legit.
	 * It first checks whether the email format is valid, and then uses some DNS checks
	 * to ensure the specified domain has an MX or an A record (both are considered valid).
	 * - May cause slowdown, because of DNS checks
	 * - Will report an email as invalid if the DNS system fails
	 * - Reports all domains as valid when using OpenDNS or a similar DNS service (every invalid domain resolves to opendns' IP address)
	 *
	 * @param string $email
	 * @return boolean
	 */
	static public function isValid($email) {
		$atIndex = strrpos($email, '@');
		if ($atIndex === false) {
			return false;
		}

		$domain = substr($email, $atIndex + 1);
		$local = substr($email, 0, $atIndex);

		$localLen = strlen($local);
		if ($localLen < 1 || $localLen > 64) {
			// local part length exceeded
			return false;
		}

		$domainLen = strlen($domain);
		if ($domainLen < 1 || $domainLen > 255) {
			// domain part length exceeded
			return false;
		}

		if ($local[0] == '.' || $local[$localLen - 1] == '.') {
			// local part starts or ends with '.'
			return false;
		}

		if (preg_match('/\\.\\./', $local)) {
			// local part has two consecutive dots
			return false;
		}

		//Domain labels ..blah blah.., ending with an alphabetic or numeric character (RFC 1035 2.3.1).
		$lastDomainChar = $domain[$domainLen - 1];
		if (!preg_match("/^[a-zA-Z0-9]$/", $lastDomainChar)) {
			return false;
		}

		if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
			// character not valid in domain part
			return false;
		}

		if (preg_match('/\\.\\./', $domain)) {
			// domain part has two consecutive dots
			return false;
		}

		if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
			// character not valid in local part unless
			// local part is quoted
			if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
				return false;
			}
		}

		//An MX or an A record are both valid
		//If there's no MX record, email is sent to the same domain by default.

		$hasRecordMx = checkdnsrr($domain, 'MX');
		if ($hasRecordMx) {
			return true;
		}

		//Checking for `invalid-domain.com` will return A matches for `invalid-domain.com.net` and similar
		//It's some dumb suggestion technique or something
		//These matches can be seen when running dns_get_record($domain, DNS_A);
		//To avoid them, we simply add a dot at the end of the domain name,
		//which tells it that it's FINAL and we don't want "suggestions".
		$hasRecordA = checkdnsrr($domain . '.', 'A');
		if ($hasRecordA) {
			return true;
		}

		return false;
	}

}
