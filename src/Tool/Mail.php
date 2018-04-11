<?php
/**
 * Tool_Mailクラス
 *
 * メールを送信するクラス
 *
 * @access public
 * @author Lionheart Co., Ltd.
 * @version 1.0.0
 */

class Tool_Mail
{
    var
        $_chara    = 'UTF-8',
        $_s_chara  = 'ISO-2022-JP',
        $_subject  = '',
        $_body     = '',
        $_header   = array(),
        $_is_debug = false,
        $_log_file = '/tmp/sendmaillog';


    function __construct( $subject = NULL, $body = NULL, $header = NULL )
    {
        if(! empty( $subject ) ) {
            $this->subject( $subject );
        }
        if(! empty( $body ) ) {
            $this->body( $body );
        }
        if(! empty( $header ) && is_array( $header ) ) {
            $this->pushHeader( $header );
        }
    }

    /**
     * 件名登録
     *
     * @param string $subject
     */
    public function subject( $subject )
    {
        $this->_subject = $subject;
    }

    /**
     * 本文登録
     *
     * @param string $body
     */
    public function body( $body )
    {
        $this->_body = $body;
    }

    /**
     * 拡張ヘッダ追加
     *
     * @param $key ヘッダキー（連想配列の場合は複数同時登録）
     * @param $val ヘッダデータ
     */
    public function pushHeader( $key, $val = NULL )
    {
        if( is_array( $key ) || is_object( $key ) ) {
            foreach( $key as $k => $v ) {
                $this->pushHeader( $k, $v );
            }
        } else {
            $this->_header[ $key ] = $val;
        }
    }

    /**
     * 拡張ヘッダ削除
     *
     * @param $key ヘッダキー（省略時は全て削除）
     */
    public function removeHeader( $key = NULL )
    {
        if(! $key ) {
            $this->_header = array();
        }

        if( isset( $this->_header[ $key ] ) ) {
            unset( $this->_header[ $key ] );
        }
    }

    /**
     * メール送信
     *
     * @param  string $to        送信先メールアドレス
     * @param  string $from_name 送信者名
     * @param  string $from_ad   送信元メールアドレス
     * @return boolean
     */
    public function send($to, $from_name = NULL, $from_ad = NULL) {
        if($this->_is_debug) {
            return $this->log($to, $from_name, $from_ad);
        }

        // 使用する言語の設定
        mb_language( "Japanese" );

        // メールヘッダを用意
        $header = array();

        // 送信元情報をメールヘッダ追加する
        if(! empty( $from_ad ) ) {
            if(! empty( $from_name ) ) {
                $from_name = $this->beforePostSetText( $from_name );
                $from_ad   = $this->beforeMimeHeaderText($from_name).'<'.$from_ad.'>';
            }
            $header[]  = 'From: '.$from_ad;
        }

        // 通常ヘッダ情報を追加する
        $header[] = 'Mime-Version: 1.0';
        $header[] = 'Content-Type: text/plain; charset='.$this->_s_chara;
        $header[] = 'Content-Transfer-Encoding: 7bit';

        // 拡張ヘッダを追加する
        foreach( $this->_header as $key => $val ) {
            $header[] = $key.': '.$this->beforeMimeHeaderText( $val );
        }
        $header = implode( "\n", $header );

        // 送信
        $subject = $this->beforeMimeHeaderText( $this->_subject );
        $body    = $this->beforePostSetText( $this->cnvToMeta( $this->_body ) );

        return mail( $to, $subject, $body, $header );
    }

    /**
     * ログを出力する
     *
     * @return boolean
     */
    public function log($to, $from_name = NULL, $from_ad = NULL)
    {

        // メールヘッダを用意
        $header = array();

        // 送信元情報をメールヘッダ追加する
        if(! empty( $from_ad ) ) {
            if(! empty( $from_name ) ) {
                $from_name = $this->replaceText($from_name);
                $from_ad   = $from_name.'<'.$from_ad.'>';
            }
            $header[]  = 'From: '.$from_ad;
        }

        // 通常ヘッダ情報を追加する
        $header[] = 'Mime-Version: 1.0';
        $header[] = 'Content-Type: text/plain; charset='.$this->_s_chara;
        $header[] = 'Content-Transfer-Encoding: 7bit';

        // 拡張ヘッダを追加する
        foreach( $this->_header as $key => $val ) {
            $header[] = $key.': '.$this->replaceText($val);
        }
        $header = implode( "\n", $header );

        // 送信
        $this->replaceText( $str );
        $subject = $this->replaceText($this->_subject);
        $body    = $this->replaceText($this->cnvToMeta($this->_body));

        if(
            is_writable($this->_log_file) ||
            (is_writable(dirname($this->_log_file)))
        ) {
            file_put_contents($this->_log_file, PHP_EOL . '============================================================' . PHP_EOL . PHP_EOL, FILE_APPEND);
            file_put_contents($this->_log_file, '[TO] : ' . $to . PHP_EOL, FILE_APPEND);
            file_put_contents($this->_log_file, '[Subject] : ' . $subject . PHP_EOL, FILE_APPEND);
            file_put_contents($this->_log_file, '[Header]' . PHP_EOL, FILE_APPEND);
            file_put_contents($this->_log_file, $header . PHP_EOL, FILE_APPEND);
            file_put_contents($this->_log_file, '[Body]' . PHP_EOL, FILE_APPEND);
            file_put_contents($this->_log_file, $body . PHP_EOL, FILE_APPEND);
        }
        return true;
    }

    /**
     * 入力文字コードを指定
     *
     * @param string $charset
     */
    public function setInputCharset( $charset )
    {
        $this->_chara = strtoupper( str_replace( array( "\r\n", "\r", "\n" ), '', $charset ) );
    }

    /**
     * メール送信文字コードを指定
     *
     * @param string $charset
     */
    public function setSendCharset( $charset )
    {
        $this->_s_chara = strtoupper( str_replace( array( "\r\n", "\r", "\n" ), '', $charset ) );
    }

    /**
     * 機種依存文字変換
     * via http://php.nekosuke.com/000056.htm
     *
     * @param string $str
     */
    private function replaceText( $str ){
        $str = mb_convert_kana( $str, "KV", $this->_chara );

        $arr = array(
            "\xE2\x85\xA0" => "I",
            "\xE2\x85\xA1" => "II",
            "\xE2\x85\xA2" => "III",
            "\xE2\x85\xA3" => "IV",
            "\xE2\x85\xA4" => "V",
            "\xE2\x85\xA5" => "VI",
            "\xE2\x85\xA6" => "VII",
            "\xE2\x85\xA7" => "VIII",
            "\xE2\x85\xA8" => "IX",
            "\xE2\x85\xA9" => "X",
            "\xE2\x85\xB0" => "i",
            "\xE2\x85\xB1" => "ii",
            "\xE2\x85\xB2" => "iii",
            "\xE2\x85\xB3" => "iv",
            "\xE2\x85\xB4" => "v",
            "\xE2\x85\xB5" => "vi",
            "\xE2\x85\xB6" => "vii",
            "\xE2\x85\xB7" => "viii",
            "\xE2\x85\xB8" => "ix",
            "\xE2\x85\xB9" => "x",
            "\xE2\x91\xA0" => "(1)",
            "\xE2\x91\xA1" => "(2)",
            "\xE2\x91\xA2" => "(3)",
            "\xE2\x91\xA3" => "(4)",
            "\xE2\x91\xA4" => "(5)",
            "\xE2\x91\xA5" => "(6)",
            "\xE2\x91\xA6" => "(7)",
            "\xE2\x91\xA7" => "(8)",
            "\xE2\x91\xA8" => "(9)",
            "\xE2\x91\xA9" => "(10)",
            "\xE2\x91\xAA" => "(11)",
            "\xE2\x91\xAB" => "(12)",
            "\xE2\x91\xAC" => "(13)",
            "\xE2\x91\xAD" => "(14)",
            "\xE2\x91\xAE" => "(15)",
            "\xE2\x91\xAF" => "(16)",
            "\xE2\x91\xB0" => "(17)",
            "\xE2\x91\xB1" => "(18)",
            "\xE2\x91\xB2" => "(19)",
            "\xE2\x91\xB3" => "(20)",
            "\xE3\x8A\xA4" => "(上)",
            "\xE3\x8A\xA5" => "(中)",
            "\xE3\x8A\xA6" => "(下)",
            "\xE3\x8A\xA7" => "(左)",
            "\xE3\x8A\xA8" => "(右)",
            "\xE3\x88\xB1" => "(株)",
            "\xE3\x88\xB2" => "(有)",
            "\xE3\x88\xB9" => "(代)"
        );
        return strtr($str, $arr);
    }

    /**
     * データをメタデータに変換
     *
     * @param  string $string
     * @return string
     */
    private function cnvToMeta($string) {
        if( is_array($string) || is_object($string) ) {
            foreach( $string as $key=>$val ) {
                $string[$key] = $this->cnvToMeta($val);
            }
        } else {
            if (get_magic_quotes_gpc()) {
                $string = stripslashes($string);
            }
            $string = str_replace("&", "＆", $string);
            $string = str_replace("\"", "”", $string);
            $string = str_replace("<", "＜", $string);
            $string = str_replace(">", "＞", $string);
            $string = str_replace(",", "，", $string);
            $string = str_replace("'", "’", $string);
            $string = str_replace("\r\n", "\n", $string);
            $string = str_replace("\r", "\n", $string);
        }
        return $string;
    }

    /**
     * 投稿用文字変換
     *
     * @param  string $str
     * @return string
     */
    private function beforePostSetText( $str ) {
        $str = $this->replaceText( $str );
        $str = mb_convert_encoding( $str, $this->_s_chara, $this->_chara );
        return $str;
    }

    /**
     * 投稿用文字変換（mimeheader）
     *
     * @param  string $str
     * @return string
     */
    private function beforeMimeHeaderText( $str ) {
        $str = $this->beforePostSetText( $str );
        mb_internal_encoding( $this->_s_chara );
        $str = mb_encode_mimeheader( $str, $this->_s_chara, "B", "\r\n" );
        mb_internal_encoding( $this->_chara );
        $str = str_replace( "\r\n", "\n", $str );
        return $str;
    }

    /**
     * デバッグフラグを設定する
     */
    public function setDebugFlag($is_debug, $log_name)
    {
        $this->_is_debug = $is_debug;
        $this->_log_file = $log_name;
    }

}