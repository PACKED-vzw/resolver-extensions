<?php

/**
 * Class that allows the exporting to happen. Is subclassed in a class per supported export format.
 * Currently supports CSV
 * Public functions:
 * base::get_all_pages (): gets all pages from the DB, processes them and stores them in ::$export (must be called first)
 * child::create_file (): creates a file in the child-format (e.g. CSV) and returns the file name
 *
 *  * (c) 2015 PACKED vzw (Pieter De Praetere)
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

class ResolverExport {

    private $db;
    private $site_url;
    private $data;
    public $export = array ();
    private $api = '/api.php?action=query&list=allpages&format=json&apnamespace=0&apfilterredir=all&aplimit=150%s';

    function __construct () {
        global $wgServer;
        $this->db = wfGetDB (DB_MASTER);
        $this->site_url = $wgServer;
    }

    /**
     * Return all pages in array [{pageid: id; title: title; ns: ns; link: link}]
     * @return array|mixed
     * @throws Exception
     */
    public function get_all_pages () {
        $this->get_pages_from_db ();
        /* Perform some preprocessing: add url & change title to fully prefixed title */
        $this->preprocess ();
        /* Add the items to the resolver table */
        $this->update_resolver_table ();
        /* Get all data to export */
        $this->export_data ();
        return $this->export;
    }

    /**
     * Get all pages from the DB
     * @return array (array(id => id; title => title; ns => ns))
     */
    protected function get_pages_from_db () {
        $r = $this->db->select (
            'page',
            array ('page_id', 'page_title', 'page_namespace')
        );
        if ($r === false) {
            $r = array ();
        }
        $p = array ();
        foreach ($r as $row) {
            array_push ($p, array (
                'id' => $row->page_id,
                'ns' => $row->page_namespace,
                'title' => $row->page_title
            ));
        }
        $this->data = $p;
    }

    /**
     * Preprocess: add full title & url
     */
    protected function preprocess () {
        array_walk ($this->data, array ('ResolverExport', 'add_link'));
        array_walk ($this->data, array ('ResolverExport', 'update_title'));
    }

    /**
     * Add the full page link
     * @param $var
     */
    private function add_link (&$var) {
        $t = Title::newFromID ($var['id']);
        $var['url'] = $t->getFullURL ();
    }

    /**
     * Update the title to include the namespace
     * @param $var
     */
    private function update_title (&$var) {
        $t = Title::newFromID ($var['id']);
        $var['title'] = $t->getFullText ();
    }

    /**
     * Update the resolver table: add all items that have not been previously exported as well as all
     * the renamed/moved pages
     * @throws Exception
     */
    protected function update_resolver_table () {
        /* For every item, check whether this combination of id & title & url is already in the table */
        /* If it is, do nothing */
        /* If it isn't, add */
        foreach ($this->data as $p) {
            $r = $this->db->selectRow (
                'resolver_export',
                array ('page_id', 'page_name'),
                array ('page_name' => $p['title'], 'page_id' => $p['id'], 'page_url' => $p['url'])
            );
            if ($r === false) {
                /* Does not exist */
                $x = $this->db->insert (
                    'resolver_export',
                    array (
                        'page_name' => $p['title'],
                        'page_id' => $p['id'],
                        'page_url' => $p['url']
                    )
                );
                if ($x != true) {
                    throw new Exception ('Error: failed to insert row in resolver_export');
                }
            }
        }
    }

    /**
     * Set $this->export to an array containing the contents of the resolver_export table
     * export: array (array(page_id => x, page_name => y, page_url => z))
     */
    protected function export_data () {
        $r = $this->db->select (
            'resolver_export',
            array ('page_id', 'page_name', 'page_url')
        );
        if ($r === false) {
            $r = array ();
        }
        foreach ($r as $p) {
            array_push ($this->export, array (
                'page_id' => $p->page_id,
                'page_name' => $p->page_name,
                'page_url' => $p->page_url
            ));
        }
    }


}

class CSVExport extends ResolverExport {
    private $csv_header = array ('PID', 'entity type', 'title', 'document type', 'URL', 'enabled', 'notes', 'format', 'reference', 'order');
    private $csv = '';
    public $filename;

    /**
     * Create a CSV-string that can be made into a document to import in the resolver
     * @return string
     * @throws Exception
     */
    protected function export_csv () {
        if (count ($this->export) == 0) {
            throw new Exception ('Error: data is empty! Call ::get_all_pages first!');
        }
        /* Output header = unicode (table is stored in unicode) */
        $h = $this->csv_header;
        array_walk ($h, array ('CSVExport', 'convert_to_ascii'));
        array_walk ($h, array ('CSVExport', 'quote_field'));
        $this->csv = implode (';', $h);
        foreach ($this->export as $e) {
            $r = $this->create_rows ($e);
            $this->csv = $this->csv."\n".implode (';', $r[0])."\n".implode (';', $r[1]);
        }
    }

    /**
     * Create a CSV file on disk & return the file name
     * @param $dir
     * @return string
     * @throws Exception
     */
    public function create_file ($dir) {
        if ($this->csv == '') {
            $this->export_csv ();
        }
        $this->filename = 'export-'.date ('c').'.csv';
        /* Escape for windows compatibility */
        $this->filename = str_replace (array ('/', '\\', ':', '*', '?', '"', '<', '>', '|', '\''), '_', $this->filename);
        $this->filename = $dir.'/'.$this->filename;
        if (file_put_contents ($this->filename, $this->csv) === false) {
            throw new Exception ('Error: failed to create CSV file!');
        }
        return basename ($this->filename);
    }

    /**
     * Create a CSV file on disk & return the file name
     * DEPRECATED
     * @param $dir
     * @return string
     * @throws Exception
     */
    public function create_csv_file ($dir) {
        return $this->create_file ($dir);
    }

    /**
     * Fields are quoted with double quotes (CSV)
     * @param $field
     */
    protected function quote_field (&$field) {
        $field = addslashes ($field);
        $field = sprintf ('"%s"', $field);
    }

    /**
     * Resolver only accepts strings encoded in ASCII (this might be an issue)
     * @param $field
     */
    protected function convert_to_ascii (&$field) {
        $field = mb_convert_encoding ($field, 'ASCII', 'UTF-8');
    }

    /**
     * Function to create the two rows per original element the resolver expects (one for data & one for representation)
     * Apart from the document_type column (key 3), they are identical
     * @param $data
     * @return array
     */
    protected function create_rows ($data) {
        $r = array (
            $data['page_id'],
            'work',
            str_replace ('_', ' ', $data['page_name']),
            'data',
            $data['page_url'],
            '1',
            '',
            'html',
            '',
            ''
        );
        $result = array ();
        array_push ($result, $r, $r);
        $result[1][3] = 'representation';
        array_walk ($result[0], array ('CSVExport', 'convert_to_ascii'));
        array_walk ($result[1], array ('CSVExport', 'convert_to_ascii'));
        array_walk ($result[0], array ('CSVExport', 'quote_field'));
        array_walk ($result[1], array ('CSVExport', 'quote_field'));
        return $result;
    }
}