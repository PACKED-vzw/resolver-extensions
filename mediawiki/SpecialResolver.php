<?php
/**
 * Special page class for the Resolver Export
 * (c) 2015 PACKED vzw (Pieter De Praetere)
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of version 3 the GNU General Public License
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

include (__DIR__.'/includes/ResolverExport.php');
/*
 * https://www.mediawiki.org/wiki/HTMLForm
 */
class SpecialResolver extends SpecialPage {

    private $request;
    private $output;
    private $export;
    public static $csv_dir;
    public static $csv_path;

    function __construct() {
        global $wgUploadDirectory;
        global $wgUploadPath;
        global $wgCanonicalServer;
        self::$csv_dir = "{$wgUploadDirectory}/CSV";
        self::$csv_path = "{$wgCanonicalServer}{$wgUploadPath}/CSV";
        parent::__construct ('Resolver', 'editinterface'); /* Sysops only */
    }

    /**
     * Execute the special page
     * @param null|string $par
     */
    function execute ($par) {
        /* Only sysops may use this page */
        if (!$this->userCanExecute ($this->getUser ())) {
            $this->displayRestrictionError ();
            return;
        }
        $this->request = $this->getRequest();
        //https://doc.wikimedia.org/mediawiki-core/master/php/classWebRequest.html
        $this->output = $this->getOutput();
        $this->output->setPageTitle ($this->get_msg('resolver-title'));
        $formDescriptor = array (
            'format' => array (
                'class' => 'HTMLSelectField',
                'label' => $this->get_msg('resolver-format-label'),
                'options' => array (
                    $this->get_msg ('resolver-csv') => 'csv'
                )
            )
        );
        $htmlForm = new HTMLForm ($formDescriptor, $this->getContext(), 'resolver-form');
        $htmlForm->setSubmitText($this->get_msg('resolver-submit'));
        $htmlForm->setSubmitCallback(array('SpecialResolver', 'onSubmit'));
        $htmlForm->show();
    }

    /**
     * Overrides parent
     * https://www.mediawiki.org/wiki/Manual:Special_pages
     */
    function getGroupName () {
        return 'maintenance';
    }

    /**
     * Function to echo a i18n-message
     * @param $key
     * @return string
     */
    protected function get_msg ($key) {
        return $this->getSkin ()->msg ($key)->escaped ();
    }

    /**
     * OnSubmit callback
     * @param $data
     * @param $htmlform
     * @return bool|string
     * @throws Exception
     */
    static function onSubmit ($data, $htmlform) {
        $d = strtolower ($data['format']);
        switch ($d) {
            case 'csv':
                $e = new CSVExport ();
                $e->get_all_pages();
                $f = $e->create_csv_file (self::$csv_dir);
                break;
            default:
                return 'Invalid export type';
                break;
        }
        $t = wfMessage ('resolver-download')->plain ();
        $t = sprintf ($t, self::$csv_path.'/'.urlencode($f));
        /*https://www.mediawiki.org/wiki/Talk:HTMLForm*/
        $out = $htmlform->getOutput ();
        $out->addWikiText ($t);
        $out->output ();
        return true;
    }
}