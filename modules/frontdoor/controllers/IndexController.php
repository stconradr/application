<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category    Application
 * @package     Module_Frontdoor
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 *
 */

class Frontdoor_IndexController extends Controller_Action {

    /**
     * Display the metadata of a document.
     *
     * @return void
     */
    public function indexAction() {
        $this->view->title = $this->view->translate('frontdoor_title');
        $request = $this->getRequest();
        $docId = $request->getParam('docId', '');
        $this->view->docId = $docId;
        $baseUrl = $request->getBaseUrl();

        if ($docId == '') {
            $this->view->errorMessage = "frontdoor_doc_id_missing";
            $this->getResponse()->setHttpResponseCode(404);
            $this->render('document-error');
            return;
        }

        $document = null;
        try {
            $document = new Opus_Document($docId);
        }
        catch (Opus_Model_NotFoundException $e) {
            $this->view->errorMessage = "frontdoor_doc_id_not_found";
            $this->getResponse()->setHttpResponseCode(404);
            $this->render('document-error');
            return;
        }

        $documentXml = null;
        try {
            $documentXml = new Util_Document($document);
        }
        catch (Application_Exception $e) {
            $this->getResponse()->setHttpResponseCode(403);
            $this->view->errorMessage = "frontdoor_doc_access_denied";

            switch ($document->getServerState()) {
                case 'deleted':
                    $this->getResponse()->setHttpResponseCode(410);
                    $this->view->errorMessage = "frontdoor_doc_deleted";
                    break;

                case 'unpublished':
                    $this->getResponse()->setHttpResponseCode(403);
                    $this->view->errorMessage = "frontdoor_doc_unpublished";
                    break;

                default:
                    break;
            }

            $this->render("document-error");
            return;
        }

        $documentNode = $documentXml->getNode(false);

        $xslt = new DomDocument;
        $template = 'index.xslt';
        $xslt->load($this->view->getScriptPath('index') . DIRECTORY_SEPARATOR . $template);
        $proc = new XSLTProcessor;
        $proc->registerPHPFunctions('Frontdoor_IndexController::translate');
        $proc->registerPHPFunctions('Frontdoor_IndexController::checkIfUserHasFileAccess');
        $proc->importStyleSheet($xslt);

        $this->view->baseUrl = $baseUrl;
        $this->view->doctype('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"  "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">');

        $dateModified = $document->getServerDateModified();
        if (!is_null($dateModified)) {
            $this->view->headMeta()
                    ->appendHttpEquiv('Last-Modified', $dateModified->getDateTime()->format(DateTime::RFC1123));
        }
        $this->addMetaTagsForDocument($document);
        $this->setFrontdoorTitleToDocumentTitle($document);

        $config = Zend_Registry::getInstance()->get('Zend_Config');
        $layoutPath = 'layouts/' . (isset($config, $config->theme) ? $config->theme : '');

        $proc->setParameter('', 'baseUrlServer', $this->getFullServerUrl());
        $proc->setParameter('', 'baseUrl', $baseUrl);
        $proc->setParameter('', 'layoutPath', $baseUrl . '/' . $layoutPath);
        $proc->setParameter('', 'isMailPossible', $this->isMailPossible($document));
        $this->view->frontdoor = $proc->transformToXML($documentNode);

        $this->incrementStatisticsCounter($docId);
    }

    /**
     *
     * @param Opus_Document $doc
     */
    private function isMailPossible($doc) {
        $authors = new Frontdoor_Model_Authors($doc);
        return count($authors->getContactableAuthors()) > 0;
    }

    private function getFullServerUrl() {
        return $this->view->serverUrl() . $this->getRequest()->getBaseUrl();
    }

    /**
     * Static function to be called from XSLT script to check file permission.
     *
     * @param string|int $file_id
     * @return boolean
     */
    public static function checkIfUserHasFileAccess($file_id = null) {
        if (is_null($file_id)) {
            return false;
        }

        $realm = Opus_Security_Realm::getInstance();
        return $realm->checkFile($file_id);
    }

    private function setFrontdoorTitleToDocumentTitle($document) {
        $docLanguage = $document->getLanguage();
        $docLanguage = is_array($docLanguage) ? $docLanguage : array($docLanguage);

        $titleStringMain = "";
        $titleStringAlt = "";

        foreach ($document->getTitleMain() AS $title) {
            $titleValue = trim($title->getValue());
            if (empty($titleValue)) {
                continue;
            }

            if (in_array($title->getValue(), $docLanguage)) {
                $titleStringMain = $titleValue;
            }
            else {
                $titleStringAlt = $titleValue;
            }
        }

        if (!empty($titleStringMain)) {
            $this->view->title = $titleStringMain;
        }
        elseif (!empty($titleStringAlt)) {
            $this->view->title = $titleStringAlt;
        }
    }

    private function addMetaTagsForDocument($document) {
        foreach ($this->createMetaTagsForDocument($document) AS $pair) {
            $this->view->headMeta($pair[1], $pair[0]);
        }
    }

    private function createMetaTagsForDocument($document) {
        $config = Zend_Registry::getInstance()->get('Zend_Config');
        $serverUrl = $this->view->serverUrl();
        $baseUrlServer = $this->getFullServerUrl();
        $baseUrlFiles = $serverUrl . (isset($config, $config->deliver->url->prefix) ? $config->deliver->url->prefix : '/documents');

        $metas = array();

        foreach ($document->getPersonAuthor() AS $author) {
            $lastname = trim($author->getLastName());
            if (empty($lastname)) {
                continue;
            }
            $name = $lastname;

            $firstname = trim($author->getFirstName());
            if (!empty($firstname)) {
                $name .= ", " . $firstname;
            }

            $metas[] = array('DC.Creator', $name);
            $metas[] = array('author', $name);
            $metas[] = array('citation_author', $name);
        }

        foreach ($document->getTitleMain() AS $title) {
            $titleValue = trim( $title->getValue() );
            if (empty($titleValue)) {
                continue;
            }
            $metas[] = array('DC.title', $titleValue);
            $metas[] = array('title', $titleValue);
            $metas[] = array('citation_title', $titleValue);
        }

        foreach ($document->getTitleAbstract() AS $abstract) {
            $abstractValue = trim( $abstract->getValue() );
            if (empty($abstractValue)) {
                continue;
            }
            $metas[] = array('DC.Description', $abstractValue);
            $metas[] = array('description', $abstractValue);
        }

        $subjectsArray = array();
        foreach ($document->getSubject() AS $subject) {
            $subjectValue = trim($subject->getValue());
            if (empty($subjectValue)) {
                continue;
            }
            $metas[] = array('DC.subject', $subjectValue);
            $subjectsArray[] = $subjectValue;
        }
        if (count($subjectsArray) > 0) {
            $subjectsArray = array_unique($subjectsArray);
            $metas[] = array('keywords', implode(", ", $subjectsArray));
        }

        foreach ($document->getIdentifierUrn() AS $identifier) {
            $identifierValue = trim($identifier->getValue());
            if (empty($identifierValue)) {
                continue;
            }
            $metas[] = array('DC.Identifier', $identifierValue);
        }
        $metas[] = array('DC.Identifier', $baseUrlServer . '/frontdoor/index/index/docId/'. $document->getId());

        foreach ($document->getFile() AS $file) {
            if (!$file->exists() or ($file->getVisibleInFrontdoor() !== '1') ) {
                continue;
            }
            $metas[] = array('DC.Identifier', "$baseUrlFiles/" . $document->getId() . "/" . $file->getPathName());

            if ($file->getMimeType() == 'application/pdf') {
                $metas[] = array('citation_pdf_url', "$baseUrlFiles/" . $document->getId() . "/" . $file->getPathName());
            }
            else if ($file->getMimeType() == 'application/postscript') {
                $metas[] = array('citation_ps_url', "$baseUrlFiles/" . $document->getId() . "/" . $file->getPathName());
            }
        }

        $datePublished = $document->getPublishedDate();
        if (!is_null($datePublished)) {
            // $date = new Opus_Date();
            $dateString = $datePublished->getZendDate()->get('yyyy-MM-dd');

            $metas[] = array("citation_date", $dateString);
            $metas[] = array("DC.Date", $dateString);
        }

        return $metas;
    }

    private function incrementStatisticsCounter($docId) {
        try {
            $statistics = Opus_Statistic_LocalCounter::getInstance();
            $statistics->countFrontdoor($docId);
        }
        catch (Exception $e) {
            $this->_logger->err("Counting frontdoor statistics failed: " . $e);
        }
    }
    
    /**
     * maps an old ID from OPUS3 to the new one in OPUS4
     * 
     * @deprecated since OPUS 4.0.3: this function will be removed in future releases
     * use Rewrite_IndexController instead
     * 
     * @return void
     */
    public function mapopus3Action() {
        $docId = $this->getRequest()->getParam('oldId');
        $this->_redirectToAndExit('id', '', 'index', 'rewrite', array('type' => 'opus3-id', 'value' => $docId));
    }

    /**
     * Gateway function to Zend's translation facilities.
     *
     * @param  string  $key The key of the string to translate.
     * @return string  The translated string.
     */
    static public function translate($key) {
        $registry = Zend_Registry::getInstance();
        $translate = $registry->get('Zend_Translate');
        return $translate->_($key);
    }

}