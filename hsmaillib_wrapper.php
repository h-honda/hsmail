<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined ( 'MOODLE_INTERNAL' ) || die ();

require_once( 'hsmaillib_common.php' );

/**
 * A wrapper for the hs_mail_lib class
 */
class hsmaillib_wrapper {

    // Email transmission FROM address.
    private $mailfrom = '';
    private $mailfromdisp = '';
    // Title of mail transmission.
    private $mailsubject = '';
    public function __construct() {
        $this->mailfrom = get_config('core', 'noreplyaddress');
        $this->mailfromdisp = 'noreply';
    }

    /**
     * Convert the parameters to use instead of the previous pear mail class
     *
     * @param unknown $recipients Destination (receiver)
     * @param unknown $headers
     * @param unknown $body
     */
    public function send($recipients, $headers, $body) {
        $to = $recipients;
        $tmpfrom = ( $headers ['From'] == '' ) ? $this->mailfrom : $headers['From'];
        $from = array (
                'address' => $tmpfrom
        );
        if (isset ( $headers ['FromName'] ) && $headers['FromName'] != '') {
            // When the from display name is specified.
            $from['name'] = $headers ['FromName'];
        }
        if ($tmpfrom == $this->mailfrom ) {
            $from['name'] = $this->mailfromdisp;
        }
        $subject = $headers ['Subject'];
        $body = $body;

        $hsmaillib = new hsmaillib_common ();
        return $hsmaillib->send_mail ( $to, $from, $subject, $body );
    }
}
