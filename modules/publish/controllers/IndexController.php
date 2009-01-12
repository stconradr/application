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
 * @package     Module_Publish
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Main entry point for this module.
 *
 * @category    Application
 * @package     Module_Publish
 */
class Publish_IndexController extends Zend_Controller_Action {

    /**
     * Redirector - defined for code completion
     *
     * @var Zend_Controller_Action_Helper_Redirector
     */
    protected $_redirector = null;

    /**
     * Holds base url
     *
     * @var string
     */
    protected $_baseUrl = null;

    /**
     * Do some initialization on startup of every action
     *
     * @return void
     */
    public function init()
    {
        $this->_redirector = $this->_helper->getHelper('Redirector');
        $this->_baseUrl = $this->getRequest()->getBasePath() . '/' . $this->getRequest()->getModuleName();
    }

    /**
     * Just to be there. No actions taken.
     *
     * @return void
     *
     */
    public function indexAction() {
        $this->view->title = 'Publish';

        $form = new Overview();
        $form->setAction($this->_baseUrl . '/index/create');
        $this->view->form = $form;
    }

    /**
     * Create, recreate and validate a document form. If it is valid store it.
     *
     * @return void
     */
    public function createAction() {
        $this->view->title = 'Publish (create)';

        if ($this->_request->isPost() === true) {
            $data = $this->_request->getPost();
            $form_builder = new Opus_Form_Builder();
            if (array_key_exists('selecttype', $data) === true) {
                $form = new Overview();
                // validate form data
                if ($form->isValid($data) === true) {
                    $filename = '../config/xmldoctypes/' .
                        $form->getValue('selecttype') .
                        '.xml';
                    if (file_exists($filename) === false) {
                        // file does not exists, back to select form
                        $this->_redirector->gotoSimple('index');
                    }
                    $type = new Opus_Document_Type($filename);
                    $document = new Opus_Model_Document(null, $type);
                    $document->setDocumentType($form->getValue('selecttype'));
                    $createForm = $form_builder->build($document);
                    $createForm->setAction($this->_baseUrl . '/index/create');
                    $this->view->form = $createForm;
                } else {
                    // submitted form data is not valid, back to select form
                    $this->view->form = $form;
                }
            } else if (array_key_exists('submit', $data) === false) {
                $form = $form_builder->buildFromPost($data);
                $form->setAction($this->_baseUrl . '/index/create');
                $this->view->form = $form;
            } else {
                $form = $form_builder->buildFromPost($data);
                if ($form->isValid($data) === true) {
                    // go ahead to upload
                    $model = $form_builder->getModelFromForm($form);
                    $form_builder->setFromPost($model, $form->getValues());
                    $id = $model->store();
                    $this->view->title = 'Publish (upload)';
                    $uploadForm = new FileUpload();
                    $uploadForm->setAction($this->_baseUrl . '/index/upload');
                    $uploadForm->DocumentId->setValue($id);
                    $this->view->form = $uploadForm;
                } else {
                    $this->view->form = $form;
                }
            }
        } else {
            // action used directly go back to main index
            $this->_redirector->gotoSimple('index');
        }
    }

    /**
     * Create form and handling file uploading
     *
     * @return void
     */
    public function uploadAction() {
        $this->view->title = 'Publish (upload)';
        $uploadForm = new FileUpload();
        $uploadForm->setAction($this->_baseUrl . '/index/upload');
        // store uploaded data in application temp dir
        if ($this->_request->isPost() === true) {
            $data = $this->_request->getPost();
            if ($uploadForm->isValid($data) === true) {
                // This works only from Zend 1.7 on
                // $upload = $uploadForm->getTransferAdapter();
                $upload = new Zend_File_Transfer_Adapter_Http();
                $files = $upload->getFileInfo();
                // TODO: Validate document id, error message on fail
                $documentId = $uploadForm->getValue('DocumentId');
                $document = new Opus_Model_Document($documentId);
                $this->view->message = 'Upload succussful!';
                foreach ($files as $file => $info) {
                    if (!$upload->isValid($file)) {
                        $this->view->message = 'Upload failed: Not a valid file!';
                        break;
                    }
                    $file = $document->addFile();
                    $file->setDocumentsId($document->getId());
                    $file->setFileLabel($uploadForm->getValue('comment'));
                    $file->setFileLanguage($uploadForm->getValue('language'));
                    $file->setFromPost($info);
                }
                $document->store();
                // reset input values fo re-displaying
                $uploadForm->reset();
                $this->view->form = $uploadForm;
            } else {
                // invalid form, populate with transmitted data
                $uploadForm->populate($data);
                $this->view->form = $uploadForm;
            }
        } else {
            // on non post request redirect to index action
            $this->_redirector->gotoSimple('index');
        }
    }
}
