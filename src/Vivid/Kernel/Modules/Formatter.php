<?php
/**
 * This file contains the init class for formatting tools.
 */

namespace Charm\Vivid\Kernel\Modules;

use Carbon\Carbon;
use Charm\Vivid\Base\Module;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;

/**
 * Class Formatter
 *
 * Formatter module
 *
 * @package Charm\Vivid\Kernel\Modules
 */
class Formatter extends Module implements ModuleInterface
{
    /**
     * Module init
     */
    public function loadModule()
    {
        // Nothing to do here yet!
    }

    /**
     * Format a date localized in a format specified in main.yaml
     *
     * @param string|Carbon $data the date
     *
     * @return bool|string
     */
    public function formatDate($data)
    {
        if ($data instanceof Carbon) {
            // Nothing to do. Great!
            $date = $data;
        } else {
            try {
                $date = Carbon::parse($data);
            } catch (\Exception $e) {
                return '';
            }
        }

        if ($date->toDateString() == '0000-00-00') {
            return '';
        }

        return $date->isoFormat(C::Config()->get('main:local.timestamps.date'));
    }

    /**
     * Format a date localized in a short format specified in main.yaml
     *
     * @param string|Carbon  $data  the date
     *
     * @return bool|string
     */
    public function formatDateShort($data)
    {
        if (!empty($data)) {
            if ($data == '0000-00-00 00:00:00' || $data == '0000-00-00') {
                return '-';
            }

            try {
                return Carbon::parse($data)->isoFormat(C::Config()->get('main:local.timestamps.dateshort'));
            } catch (\Exception $e) {
                return '';
            }
        }
        return false;
    }

    /**
     * Format a date with time localized in a short format specified in main.yaml
     *
     * @param string|Carbon  $data  the date
     *
     * @return bool|string
     */
    public function formatDateTimeShort($data)
    {
        if (!empty($data)) {
            if ($data == '0000-00-00 00:00:00' || $data == '0000-00-00') {
                return '-';
            }

            try {
                return Carbon::parse($data)->isoFormat(C::Config()->get('main:local.timestamps.datetimeshort'));
            } catch (\Exception $e) {
                return '';
            }
        }
        return false;
    }

    /**
     * Format a date as human diff (relative, e.g. 3 months ago)
     *
     * Will return the date (see formatDate()) if longer ago than $date_after_days days
     *
     * @param string|Carbon $date            input date as string or carbon object
     * @param int           $date_after_days return date instead of diff if older than this. Set 0 to disable
     *
     * @return string
     */
    public function formatDateDiff(Carbon|string $date, int $date_after_days = 365) : string
    {
        if(!is_object($date)) {
            $date = Carbon::parse($date);
        }

        $diff = $date->diffInDays();

        if($diff > 365 && $date_after_days != 0) {
            return $this->formatDateShort($date);
        }

        return $date->diffForHumans();
    }

    /**
     * Format a number for displaying
     *
     * @param numeric      $no        input value
     * @param int          $decimals  (opt.) the decimals (default: 2)
     * @param string|null  $decimal   (opt.) decimal separator
     * @param string|null  $thousands (opt.) thousands separator
     *
     * @return int|string
     */
    public function formatNumber($no, $decimals = 2, $decimal = null, $thousands = null)
    {
        if($decimal === null) {
            $decimal = C::Config()->get('main:local.formatting.decimal');
        }
        if($thousands === null) {
            $thousands = C::Config()->get('main:local.formatting.thousands');
        }

        if (!empty($no)) {
            return number_format((float)$no, $decimals, $decimal, $thousands);
        }
        return 0;
    }

    /**
     * Format bytes to B / KB / MB / GB / ...
     *
     * Code from: http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
     *
     * @param int  $bytes      input bytes
     * @param int  $precision  precision of return value
     *
     * @return string
     */
    public function formatBytes($bytes, $precision = 0)
    {
        $size = ['B','KB','MB','GB','TB','PB','EB','ZB','YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$precision}f", $bytes / pow(1024, $factor)) . " " . @$size[$factor];
    }

    /**
     * Sanitize an e-mail
     *
     * @param string  $input  the e-mail
     *
     * @return string
     */
    public function sanitizeEmail($input)
    {
        $input = filter_var($input, \FILTER_SANITIZE_EMAIL);

        $input = trim($input);

        // Famous gmail fix
        $input = str_replace("@googlemail.", "@gmail.", $input);

        return strtolower($input);
    }

    /**
     * Slugify a text string
     *
     * Ideas taken from:
     * https://github.com/django/django/blob/master/django/utils/text.py
     * https://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
     * https://gist.github.com/sgmurphy/3098978
     *
     * Convert spaces or repeated
     * dashes to single dashes. Remove characters that aren't alphanumerics,
     * underscores, or hyphens. Convert to lowercase. Also strip leading and
     * trailing whitespace, dashes, and underscores.
     * But keeps and translates umlaute, so we have beautiful names or links.
     *
     * @param string $text
     *
     * @return string
     */
    public function slugify(string $text) : string
    {
        // Lowercase
        $text = strtolower($text);

        // Replace umlaute etc.
        // Character map from: https://gist.github.com/sgmurphy/3098978
        $char_map = [
            // Latin
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y',
            // Latin symbols
            '©' => '(c)',
            // Greek
            'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
            'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
            'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
            'Ϋ' => 'Y',
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
            'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
            'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
            'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
            'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
            // Russian
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
            'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya',
            // Ukrainian
            'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
            // Czech
            'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
            'Ž' => 'Z',
            'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
            'ž' => 'z',
            // Polish
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ś' => 'S', 'Ź' => 'Z',
            'Ż' => 'Z',
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z',
            // Latvian
            'Ā' => 'A', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N', 'Ū' => 'u',
            'ā' => 'a', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
            'ū' => 'u'
        ];

        str_replace(array_keys($char_map), $char_map, $text);

        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Transliterate if host supports it
        if(function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Trim
        $text = trim($text, ' -_');

        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // Lowercase again
        return strtolower($text);
    }

    /**
     * Get language name string for translation (e.g. "en" or "de")
     *
     * @return string
     */
    public function getLanguage(): string
    {
        $lang = C::Request()->get('charm_lang');

        if(C::has('Session')) {
            $lang = C::Session()->get('charm_lang');
        }

        if(empty($lang)) {
            $lang = C::Config()->get('main:session.default_language', 'en');
        }
        return $lang;
    }

    /**
     * Set the language
     *
     * @param string $lang language string, e.g. "en" or "de"
     */
    public function setLanguage(string $lang): void
    {
        if(C::has('Session')) {
            C::Session()->set('charm_lang', $lang);
        }
        C::Request()->set('charm_lang', $lang);
    }

    /**
     * Automatically set language based on detection
     */
    public function setAutoLanguage()
    {
        // Not set -> detect and set language
        if(!C::Session()->has('charm_lang')) {
            // Default
            $language = C::Config()->get('main:session.default_language', 'en');

            $lang_header = C::Request()->getHeader('Accept-Language');
            if(!empty($lang_header)) {
                foreach(C::Config()->get('main:session.available_languages', []) as $lang) {
                    if(str_contains($lang_header, $lang)) {
                        $language = $lang;
                        break;
                    }
                }
            }

            $this->setLanguage($language);
        }

        // Manual override
        $lang = C::Request()->get('lang');
        if(!empty($lang) && in_array($lang, C::Config()->get('main:session.available_languages', []))) {
            $this->setLanguage($lang);
        }
    }

    /**
     * Translate a text string
     *
     * The text can include variables, like {name}. Key needs to be lowercase.
     * This will be replaced by the value of $vars['name'].
     *
     * If text was not found, $default will be used.
     *
     * Language file must be in: app/Config/Lang/$language/$key.yaml
     *
     * The language will be session key 'charm_lang',
     * if not set config main:session.default_language
     *
     * @param string $key
     * @param array $vars
     * @param null|mixed  $default
     *
     * @return mixed
     */
    public function translate(string $key, $vars = [], $default = null)
    {
        $text = C::Config()->get(
            'Lang/' . $this->getLanguage() . '/' . $key,
            $default
        );

        if(is_string($text)) {
            foreach ($vars as $k => $v) {
                $text = str_replace('{' . strtolower($k) . '}', $v, $text);
            }
        }

        return $text;
    }

    public function percentageChange($old_price, $new_price): float|int
    {
        return ($new_price - $old_price) / $old_price * 100;
    }

}