<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 */

/**
 * CRUD Controller for Opus Application.
 *
 * Dieser Controller implementiert für Opus Modelle die grundlegenden Funktionen C(reate) R(ead) U(pdate) D(elete). Um
 * ihn zu nutzen muss er mit einer konkreten Klasse erweitert werden, in der die Member-Variable $formClass gesetzt
 * wird, zum Beispiel auf 'Admin_Form_Licence' wie im LicenceController im Admin Modul.
 *
 * Actions:
 * index        GET Zeige alle Modelle
 * show         GET Zeige Model
 * new          GET/POST Zeige neues Formular/Speichere neues Model
 * edit         GET/POST Zeige Model im Formular/Speichere Model
 * delete       GET/POST Zeige Bestätigungsformular/Lösche Model
 *
 * Mögliche Ergebnisse:
 * - Redirect Aufgrund invalider ID
 * - Redirect zu Index (nach success)
 * - Redirect mit Exception beim Delete
 * - Redirect mit Exception beim Speichern)
 * - Formular anzeigen
 *
 * @category    Application
 * @package     Application_Controller
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2009-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */
class Application_Controller_ActionCRUD extends Controller_Action {

    /**
     * Message-Key für erfolgreiches Abspeichern.
     */
    const SAVE_SUCCESS = 'saveSuccess';

    /**
     * Message-Key für fehlgeschlagenes Abspeichern.
     */
    const SAVE_FAILURE = 'saveFailure';

    /**
     * Message-Key für erfolgreiches Löschen.
     */
    const DELETE_SUCCESS = 'deleteSuccess';

    /**
     * Message-Key für fehlgeschlagenes Löschen.
     */
    const DELETE_FAILURE = 'deleteFailure';

    /**
     * Message-Key für invalide oder fehlende Model-ID.
     */
    const INVALID_ID = 'invalidId';

    /**
     * Nachrichten für die verschiedenen Ereignisse.
     * @var array
     */
    private $messageTemplates;

    /**
     * Default Messages für die verschiedenen Ereignisse.
     * @var array
     */
    private $defaultMessageTemplates = array(
        self::SAVE_SUCCESS => 'controller_crud_save_success',
        self::SAVE_FAILURE => array('failure' => 'controller_crud_save_failure'),
        self::DELETE_SUCCESS => 'controller_crud_delete_success',
        self::DELETE_FAILURE => array('failure' => 'controller_crud_delete_failure'),
        self::INVALID_ID => array('failure' => 'controller_crud_invalid_id')
    );

    /**
     * Name von Parameter für Model-ID.
     */
    const PARAM_MODEL_ID = 'id';

    /**
     * Klasse für Model-Formular.
     * @var \Application_Form_IModel
     */
    private $formClass = null;

    /**
     * Klasse für OPUS Model.
     * @var \Opus_Model_Abstract
     */
    private $modelClass = null;

    /**
     * Initialisiert den Controller.
     */
    public function init() {
        parent::init();
        $this->loadDefaultMessages();
    }

    /**
     * List all available model instances
     *
     * @return void
     * 
     * TODO Konfigurierbare Tabelle mit Links für Editing/Deleting
     */
    public function indexAction() {
        $form = new Application_Form_Model_Table();
        $form->setModels($this->getAllModels());
        $form->setColumns(array(array('label' => $this->getModelClass())));
        $this->renderForm($form);
    }

    /**
     * Zeigt das Model an.
     *
     * Für die Anzeige wird das Model-Formular im "View"-Modus verwendet.
     *
     * @return void
     */
    public function showAction() {
        $model = $this->getModel($this->getRequest()->getParam(self::PARAM_MODEL_ID));

        if (!is_null($model)) {
            $form = $this->getEditModelForm($model);
            $form->prepareRenderingAsView();
            $result = $form;
        }
        else {
            $result = $this->createInvalidIdResult();
        }

        $this->renderResult($result);
    }

    /**
     * Zeigt Formular für neues Model und erzeugt neues Model.
     *
     * @return void
     */
    public function newAction() {
        if ($this->getRequest()->isPost()) {
            // Formular POST verarbeiten
            $result = $this->handleModelPost();
        }
        else {
            // Neues Formular anlegen
            $form = $this->getNewModelForm();
            $form->setAction($this->view->url(array('action' => 'new')));
            $result = $form;
        }

        $this->renderResult($result);
    }

    /**
     * Edits a model instance
     *
     * @return void
     */
    public function editAction() {
        if ($this->getRequest()->isPost()) {
            // Formular POST verarbeiten
            $result = $this->handleModelPost();
        }
        else {
            // Neues Formular anzeigen
            $model = $this->getModel($this->getRequest()->getParam(self::PARAM_MODEL_ID));

            if (!is_null($model)) {
                $form = $this->getEditModelForm($model);
                $form->setAction($this->view->url(array('action' => 'edit')));
                $result = $form;
            }
            else {
                $result = $this->createInvalidIdResult();
            }
        }

        $this->renderResult($result);
    }

    /**
     * Löscht eine Model-Instanz nachdem, die Löschung in einem Formular bestätigt wurde.
     */
    public function deleteAction() {
        if ($this->getRequest()->isPost() === true) {
            // Bestätigungsformular POST verarbeiten
            $result = $this->handleConfirmationPost();
        }
        else {
            // Bestätigungsformular anzeigen
            $model = $this->getModel($this->getRequest()->getParam(self::PARAM_MODEL_ID));
            if (!is_null($model)) {
                $form = $this->getConfirmationForm($model);
                $result = $form;
            }
            else {
                // Request mit invaliden IDs werden ignoriert und zur Index Seite umgeleitet
                $result = $this->createInvalidIdResult();
            }
        }

        $this->renderResult($result);
    }

    /**
     * Speicher neues/editiertes Model.
     *
     * Ein POST kann nur Save oder Cancel bedeuten.
     */
    public function handleModelPost($post = null) {
        if (is_null($post)) {
            $post = $this->getRequest()->getPost();
        }

        $form = $this->getModelForm();
        $form->populate($post);

        $result = $form->processPost($post, $post);

        switch ($result) {
            case Application_Form_Model_Abstract::RESULT_SAVE:
                if ($form->isValid($post)) {
                    // Validierung erfolgreich; Hole Model vom Formular
                    try {
                        $model = $form->getModel();
                    }
                    catch (Application_Exception $ae) {
                        $this->getLogger()->err(__METHOD__ . $ae->getMessage());
                        $model = null;
                    }

                    if (!is_null($model)) {
                        try {
                            $model->store();
                        }
                        catch (Opus_Model_Exception $ome) {
                            // Speichern fehlgeschlagen
                            return array('message' => self::SAVE_FAILURE);
                        }

                        // Redirect zur Show Action
                        return array('action' => 'show', 'message' => self::SAVE_SUCCESS,
                            'params' =>array(self::PARAM_MODEL_ID => $model->getId()));
                    }
                    else {
                        // Formular hat kein Model geliefert - Fehler beim speichern
                        return $this->createInvalidIdResult();
                    }
                }
                else {
                    // Validierung fehlgeschlagen; zeige Formular wieder an
                    $form->populate($post); // Validierung entfernt invalide Werte
                }
                break;
            case Application_Form_Model_Abstract::RESULT_CANCEL:
            default:
                return array();

        }

        return $form;
    }

    /**
     * Verarbeitet POST vom Bestätigunsformular.
     *
     */
    public function handleConfirmationPost($post = null) {
        if (is_null($post)) {
            $post = $this->getRequest()->getPost();
        }

        $form = $this->getConfirmationForm();

        if ($form->isConfirmed($post)) {
            // Löschen bestätigt (Ja)
            $modelId = $form->getModelId();
            $model = $this->getModel($modelId);

            if (!is_null($model)) {
                // Model löschen
                try {
                    $this->deleteModel($model);
                }
                catch (Opus_Model_Exception $ome) {
                    $this->getLogger()->err(__METHOD__ . ' ' . $ome->getMessage());
                    return array('message' => self::DELETE_FAILURE);
                }

                return array('message' => self::DELETE_SUCCESS);
            }
        }
        else {
            // Löschen abgebrochen (Nein) - bzw. Formular nicht valide
            if (!$form->hasErrors()) {
                // keine Validierungsfehler
                return array();
            }
        }

        // ID war invalid oder hat im POST gefehlt (ID in Formular required)
        return $this->createInvalidIdResult();
    }

    /**
     * Setzt das Ergebnis der Verarbeitung um.
     *
     * Es wird entweder ein Formular ausgeben oder ein Redirect veranlasst.
     */
    protected function renderResult($result) {
        if (is_array($result)) {
            $action = array_key_exists('action', $result) ? $result['action'] : 'index';
            $params = array_key_exists('params', $result) ? $result['params'] : array();

            $messageKey = array_key_exists('message', $result) ? $result['message'] : null;
            $message = !is_null($messageKey) ? $this->getMessage($messageKey) : null;

            $this->_redirectTo($action, $message, null, null, $params);
        }
        else {
            // Ergebnis ist Formular
            if (!is_null($result) && $result instanceof Zend_Form) {
                $this->renderForm($result);
            }
        }
    }

    /**
     * Löscht ein Model.
     *
     * Die Funktion kann überschrieben werden, falls spezielle Schritte beim Löschen notwendig sind.
     *
     * @param $model \Opus_Model_Abstract
     */
    protected function deleteModel($model) {
        $model->delete();
    }

    /**
     * Fuehrt Redirect fuer eine ungueltige Model-ID aus.
     */
    public function createInvalidIdResult() {
        return array('message' => self::INVALID_ID);
    }

    /**
     * Erzeugt ein Bestätigunsformular für ein Model.
     *
     * Das Bestätigunsformular ohne Model wird für die Validierung verwendet.
     *
     * @param Opus_Model_AbstractDb $model
     * @return Application_Form_Confirmation
     */
    public function getConfirmationForm($model = null) {
        $form = new Application_Form_Confirmation($this->getModelClass());

        if (!is_null($model)) {
            $form->setModel($model);
        }

        return $form;
    }

    /**
     * Liefert alle Instanzen der Model-Klasse.
     */
    public function getAllModels() {
        return call_user_func(array($this->getModelClass(), 'getAll'));
    }

    /**
     * Erzeugt neue Instanz von Model-Klasse.
     * @return mixed
     */
    public function getNewModel() {
        $modelClass = $this->getModelClass();
        return new $modelClass();
    }

    /**
     * Liefert Instanz des Models.
     * @param type $modelId
     * @return \modelClass
     */
    public function getModel($modelId) {
        if (is_null($modelId) || is_numeric($modelId)) {
            $modelClass = $this->getModelClass();

            if (strlen(trim($modelId)) !== 0) {
                try {
                    return new $modelClass($modelId);
                }
                catch (Opus_Model_NotFoundException $omnfe) {
                    $this->getLogger()->err(__METHOD__ . ':' . $omnfe->getMessage());
                }
            }
        }

        return null; // keine gültige ID
    }

    /**
     * Erzeugt Formular.
     * @return Application_Form_IModel
     */
    public function getModelForm() {
        return new $this->formClass();
    }

    /**
     * Erzeugt Formular zum Editieren von Model.
     * @param $model
     * @return Application_Form_IModel
     */
    public function getEditModelForm($model) {
        $form = $this->getModelForm();
        $form->populateFromModel($model);
        return $form;
    }

    /**
     * Erzeugt Formular zum Hinzufügen eines neuen Models.
     * @return Application_Form_IModel
     */
    public function getNewModelForm() {
        $model = $this->getNewModel();
        $form = $this->getModelForm();
        $form->populateFromModel($model); // um evtl. Defaultwerte des Models zu setzen
        return $form;
    }

    /**
     * Liefert Formularklasse für Controller.
     * @return Application_Form_IModel|null
     */
    public function getFormClass() {
        return $this->formClass;
    }

    /**
     * Setzt die Model-Klasse die verwaltet wird.
     * @param $modelClass Name von Opus Model Klasse
     */
    public function setFormClass($formClass) {
        if (!$this->isClassSupported($formClass)) {
            throw new Application_Exception("Class '$formClass' is not instance of Application_Form_IModel.");
        }

        $this->formClass = $formClass;
    }

    /**
     * Liefert die Model-Klasse die verwaltet wird.
     * @return null|Opus_Model_Abstract
     */
    public function getModelClass() {
        if (is_null($this->modelClass)) {
            $this->modelClass = $this->getModelForm()->getModelClass();
        }

        return $this->modelClass;
    }

    /**
     * Prüft ob eine Formularklasse vom Controller unterstützt wird.
     * @param $formClass Name der Formularklasse
     * @return bool TRUE - wenn die Klasse unterstützt wird; FALSE - wenn nicht
     */
    public function isClassSupported($formClass) {
        $form = new $formClass();
        return ($form instanceof Application_Form_IModel) ? true : false;
    }

    /**
     * Liefert die konfigurierten Nachrichten.
     * @return array
     */
    public function getMessages() {
        return $this->messageTemplates->getMessages();
    }

    /**
     * Setzt die Nachrichten.
     * @param $messages
     */
    public function setMessages($messages) {
        $this->messageTemplates->setMessages($messages);
    }

    /**
     * Liefert die Nachricht für den Schlüssel.
     * @param $key Nachrichtenschlüssel
     * @return null|string
     */
    public function getMessage($key) {
        return $this->messageTemplates->getMessage($key);
    }

    /**
     * Setzt die Nachricht für einen Schlüssel.
     * @param $key Nachrichtenschlüssel
     * @param $message Nachricht
     */
    public function setMessage($key, $message) {
        $this->messageTemplates->setMessage($key, $message);
    }

    /**
     * Lädt die Standardnachrichten.
     */
    public function loadDefaultMessages() {
        $this->messageTemplates = new Application_Controller_MessageTemplates($this->defaultMessageTemplates);
    }

}