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

class hsmaillib_common {

    // Email transmission FROM address.
    private $mailfrom = '';
    private $mailfromdisp = '';
    // Title of mail transmission.
    private $mailsubject = '';
    public function __construct() {
    }

    /**
     * Mail transmission process
     *
     * @param string $to Destination
     * @param array $from sender array('address'=>Source address, name => Source display name)
     * @param string $subject Mail title
     * @param string $body the content of the email
     * @param unknown $attachmentfile Attachment.array('file_name'=>file name, file_body=>String of file contents)
     * @return boolean
     */
    public function send_mail($to = null, $from = array(), $subject = null, $body = null, $attachmentfiles = array()) {
        // Execute e-mail transmission.
        $mail = get_mailer ();
        mb_language ( 'uni' );
        mb_internal_encoding ( 'UTF-8' );
        $mail->CharSet = 'UTF-8';

        // If there is no destination specified, use default destination.
        if ( empty ( $to ) ) {

            // An error occurs if the default destination can not be obtained.
            if ( ! $to ) {
                return false;
            }
        } else {
            $tos = explode ( ',', $to );
        }

        // Sender. If not specified, use default source.
        if ( empty ( $from ) ) {
            $from = array (
                    'address' => $this->get_oujadress (),
                    'name' => $this->mail_from_disp
            );
        }
        $mail->From = $from ['address'];
        if ( isset ( $from ['name'] ) ) {
            $mail->FromName = mb_encode_mimeheader ( $from ['name'] );
        }

        // Mail title. When there is no designation, use default mail title.
        if ( empty ( $subject ) ) {
            $subject = $this->mail_subject;
        }
        $mail->Subject = mb_encode_mimeheader ( $subject );

        // The content of the email.
        $mail->Body = $body;
        // If attached file is specified, attach it.
        foreach ($attachmentfiles as $attachmentfile) {
            if (isset ( $attachmentfile ['file_body'] ) && isset ( $attachmentfile ['file_name'] )) {
                $mimetype = $attachmentfile ['mimetype'];

                $mail->addStringAttachment ( $attachmentfile ['file_body'],
                        mb_encode_mimeheader ( $attachmentfile ['file_name'] ), 'base64', $mimetype );
            }
        }
        // Transmission processing.
        foreach ($tos as $to) {
            $mail->clearAddresses ();
            $mail->AddAddress ( $to );
            // Error when transmission failed.
                return false;
        }
        return true;
    }

    /**
     * Obtaining the system's no-reply address
     *
     * @return boolean|NULL[]
     */
    private function get_oujadress() {
        global $DB;
        // Transmission information.
        $to = $DB->get_field ( 'config', 'value', array (
                'name' => 'noreplyaddress'
        ) );
        if ( $to === false ) {
            return false;
        }
        // If there is no address registration in the table, an error.
        if ( empty ( $to ) ) {
            return false;
        }

        return $to;
    }
}
