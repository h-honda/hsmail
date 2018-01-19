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

    // メール送信FROMアドレス
    private $mailfrom = '';
    private $mailfromdisp = '';
    // メール送信のタイトル
    private $mailsubject = '';
    public function __construct() {
    }

    /**
     * メール送信処理
     *
     * @param string $to 送信先
     * @param array $from 送信元 array('address'=>送信元アドレス, name => 送信元表示名)
     * @param string $subject メールタイトル
     * @param string $body メール本文
     * @param unknown $attachmentfile 添付ファイル。array('file_name'=>ファイル名, file_body=>ファイル内容の文字列)
     * @return boolean
     */
    public function send_mail($to = null, $from = array(), $subject = null, $body = null, $attachmentfiles = array()) {
        // メール送信実行
        $mail = get_mailer ();
        mb_language ( 'uni' );
        mb_internal_encoding ( 'UTF-8' );
        $mail->CharSet = 'UTF-8';

        // 送信先の指定がない場合は、デフォルト送信先を使用する
        if ( empty ( $to ) ) {

            // デフォルトの送信先も取得できない場合にはエラー
            if ( ! $to ) {
                return false;
            }
        } else {
            $tos = explode ( ',', $to );
        }

        // 送信元。指定がない場合は、デフォルトの送信元を使用
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

        // メールタイトル 。指定がない場合はデフォルトのメールタイトル使用
        if ( empty ( $subject ) ) {
            $subject = $this->mail_subject;
        }
        $mail->Subject = mb_encode_mimeheader ( $subject );

        // メール本文
        $mail->Body = $body;
        // 添付ファイルの指定がある場合には、添付する
        foreach ($attachmentfiles as $attachmentfile) {
            if (isset ( $attachmentfile ['file_body'] ) && isset ( $attachmentfile ['file_name'] )) {
                $mimetype = $attachmentfile ['mimetype'];

                $mail->addStringAttachment ( $attachmentfile ['file_body'], mb_encode_mimeheader ( $attachmentfile ['file_name'] ), 'base64', $mimetype );
            }
        }
        // 送信処理
        foreach ($tos as $to) {
            $mail->clearAddresses ();
            $mail->AddAddress ( $to );
            // 送信失敗時にはエラー
            if ( ! $mail->send () ) {
                return false;
            }
        }
        return true;
    }

    /**
     * システムの　no-replyアドレスの取得
     *
     * @return boolean|NULL[]
     */
    private function get_oujadress() {
        global $DB;
        // 送信情報
        $to = $DB->get_field ( 'config', 'value', array (
                'name' => 'noreplyaddress'
        ) );
        if ( $to === false ) {
            return false;
        }
        // テーブルにアドレス登録がない場合は、エラー
        if ( empty ( $to ) ) {
            return false;
        }

        return $to;
    }
}
