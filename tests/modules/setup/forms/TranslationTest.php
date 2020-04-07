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
 * @package     Module_Setup
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 */
class Setup_Form_TranslationTest extends ControllerTestCase
{

    protected $additionalResources = 'Translation';

    public function tearDown()
    {
        $database = new Opus_Translate_Dao();
        $database->removeAll();

        parent::tearDown(); // TODO: Change the autogenerated stub
    }

    public function testInit()
    {
        $form = $this->getForm();

        $elements = $form->getElements();

        $this->assertCount(5, $elements);

        $subforms = $form->getSubForms();

        $this->assertCount(1, $subforms);
    }

    public function testProcessPostSave()
    {
        $form = $this->getForm();

        $result = $form->processPost([
            'Save' => 'Speichern'
        ], null);

        $this->assertEquals($form::RESULT_SAVE, $result);
    }

    public function testProcessPostCancel()
    {
        $form = $this->getForm();

        $result = $form->processPost([
            'Cancel' => 'Abbrechen'
        ], null);

        $this->assertEquals($form::RESULT_CANCEL, $result);
    }

    public function testIsValidTrue()
    {
        $form = $this->getForm();

        $this->assertTrue($form->isValid([
            'Id' => 'translation_key',
            'Key' => 'translation_key',
            'KeyModule' => 'default',
            'Translation' => [
                'en' => 'Englisch',
                'de' => 'Deutsch'
            ],
            'Save' => 'Speichern'
        ]));
    }

    public function testIsValidUnknownModule()
    {
        $form = $this->getForm();

        $result = $form->isValid([
            'Key' => 'translation_key',
            'KeyModule' => 'defaultUnknown',
            'Translation' => [
                'en' => 'English',
                'de' => 'Deutsch'
            ],
            'Save' => 'Speichern'
        ]);

        $this->assertFalse($result);

        $errors = $form->getErrors('KeyModule');
        $this->assertContains('notInArray', $errors);
        $this->assertCount(1, $errors);
    }

    public function testIsValidEmptyValues()
    {
        $form = $this->getForm();

        $languages = Application_Configuration::getInstance()->getSupportedLanguages();
        $this->assertCount(2, $languages);
        $this->assertEquals(['de', 'en'], $languages);

        $result = $form->isValid([
            'Key' => '',
            'KeyModule' => '',
            'Translation' => [
                'en' => '',
                'de' => ''
            ],
            'Save' => 'Speichern'
        ]);

        $this->assertFalse($result);

        $errors = $form->getErrors('Key');
        $this->assertContains('isEmpty', $errors);
        $this->assertContains('regexNotMatch', $errors);
        $this->assertCount(2, $errors);

        $errors = $form->getErrors('KeyModule');
        $this->assertContains('isEmpty', $errors);
        $this->assertContains('notInArray', $errors);
        $this->assertCount(2, $errors);

        $errors = $form->getSubForm($form::SUBFORM_TRANSLATION)->getErrors('de');
        $this->assertCount(0, $errors);

        $errors = $form->getSubForm($form::SUBFORM_TRANSLATION)->getErrors('en');
        $this->assertCount(0, $errors);
    }

    public function testIsValidFalseKeyFormat()
    {
        $form = $this->getForm();

        // starting with number not allowed
        $this->assertFalse($form->isValid([
            'Key' => '123_abc'
        ]));

        $errors = $form->getErrors($form::ELEMENT_KEY);
        $this->assertCount(1, $errors);
        $this->assertContains('regexNotMatch', $errors);

        // dash not allowed
        $this->assertFalse($form->isValid([
            'Key' => 'abc-123'
        ]));

        $errors = $form->getErrors($form::ELEMENT_KEY);
        $this->assertCount(1, $errors);
        $this->assertContains('regexNotMatch', $errors);
    }

    public function testIsValidKeyTooLong()
    {
        $form = $this->getForm();

        $this->assertFalse($form->isValid([
            'Key' => 'key_' . str_repeat('0123456789', 10)
        ]));

        $errors = $form->getErrors($form::ELEMENT_KEY);

        $this->assertCount(1, $errors);
        $this->assertContains('stringLengthTooLong', $errors);
    }

    public function testIsValidDuplicateKey()
    {
        $form = $this->getForm();

        $key = 'testkey835';

        $database = new Opus_Translate_Dao();
        $database->setTranslation($key, [
            'en' => 'English',
            'de' => 'Deutsch'
        ], 'admin');

        $translate = Zend_Registry::get('Zend_Translate');
        $translate->loadDatabase(true);

        $this->assertFalse($form->isValid([
            'Key' => $key
        ]));

        $errors = $form->getErrors($form::ELEMENT_KEY);

        $this->assertCount(1, $errors);
        $this->assertContains('isNotAvailable', $errors);
    }

    public function testPopulateFromKey()
    {
        $form = $this->getForm();

        $key = 'admin_document_add';

        $form->populateFromKey($key);

        $this->assertEquals($key, $form->getElement($form::ELEMENT_KEY)->getValue());
        $this->assertTrue($form->getElement($form::ELEMENT_KEY)->getAttrib('disabled'));
        $this->assertEquals('admin', $form->getElement($form::ELEMENT_MODULE)->getValue());
        $this->assertTrue($form->getElement($form::ELEMENT_MODULE)->getAttrib('disabled'));
        $this->assertEquals([
            'en' => 'Add Metadata',
            'de' => 'Metadaten hinzufügen'
        ], $form->getSubForm($form::SUBFORM_TRANSLATION)->getTranslations());
    }

    public function testPopulateFromKeyAddedKey()
    {
        $form = $this->getForm();

        $key = 'testkey123';

        $database = new Opus_Translate_Dao();
        $database->setTranslation($key, [
            'en' => 'English',
            'de' => 'Deutsch'
        ], 'publish');

        $form->populateFromKey($key);

        $this->assertEquals($key, $form->getElement($form::ELEMENT_KEY)->getValue());
        $this->assertNull($form->getElement($form::ELEMENT_KEY)->getAttrib('disabled'));
        $this->assertEquals('publish', $form->getElement($form::ELEMENT_MODULE)->getValue());
        $this->assertNull($form->getElement($form::ELEMENT_MODULE)->getAttrib('disabled'));
        $this->assertEquals([
            'en' => 'English',
            'de' => 'Deutsch'
        ], $form->getSubForm($form::SUBFORM_TRANSLATION)->getTranslations());
    }

    /**
     * @expectedException \Opus\Translate\UnknownTranslationKey
     * @expectedExceptionMessage unknownKey789
     */
    public function testPopulateFromKeyUnknownKey()
    {
        $form = $this->getForm();

        $form->populateFromKey('unknownKey789');
    }

    public function testUpdateTranslations()
    {
        $form = $this->getForm();

        $form->populateFromKey('default_add');

        $this->assertEquals(
            'Add',
            $form->getSubForm($form::SUBFORM_TRANSLATION)->getElement('en')->getValue()
        );
        $this->assertEquals(
            'Hinzufügen',
            $form->getSubForm($form::SUBFORM_TRANSLATION)->getElement('de')->getValue()
        );

        $form->getSubForm($form::SUBFORM_TRANSLATION)->setTranslations([
            'en' => 'AddEdited',
            'de' => 'HinzufügenEdited'
        ]);

        $form->updateTranslation();

        $manager = new Application_Translate_TranslationManager();

        $translation = $manager->getTranslation('default_add');

        $this->assertEquals('default', $translation['module']);
        $this->assertEquals('AddEdited', $translation['translations']['en']);
        $this->assertEquals('HinzufügenEdited', $translation['translations']['de']);
    }

    public function testUpdateModuleOfAddedKey()
    {
        $form = $this->getForm();

        $dao = new Opus_Translate_Dao();

        $key = 'newkey1';

        $dao->setTranslation($key, [
            'en' => 'English',
            'de' => 'Deutsch'
        ], 'admin');

        $form->populateFromKey($key);

        $this->assertEquals('admin', $form->getElement($form::ELEMENT_MODULE)->getValue());

        $form->getElement($form::ELEMENT_MODULE)->setValue('publish');

        $form->updateTranslation();

        $manager = new Application_Translate_TranslationManager();

        $translation = $manager->getTranslation($key);

        $this->assertEquals('publish', $translation['module']);
    }

    public function testUpdateNameOfAddedKey()
    {
        $form = $this->getForm();

        $dao = new Opus_Translate_Dao();

        $oldKey = 'oldkey1';
        $newKey = 'newkey2';

        $dao->setTranslation($oldKey, [
            'en' => 'English',
            'de' => 'Deutsch'
        ], 'admin');

        $form->populateFromKey($oldKey);

        $this->assertEquals('admin', $form->getElement($form::ELEMENT_MODULE)->getValue());

        $form->getElement($form::ELEMENT_KEY)->setValue($newKey);

        $form->updateTranslation();

        $manager = new Application_Translate_TranslationManager();

        $failed = true;
        try {
            $translation = $manager->getTranslation($oldKey);
        } catch (\Opus\Translate\UnknownTranslationKey $ex) {
            $failed = false;
        }
        if ($failed) {
            $this->fail("Key '$oldKey' should have been removed.");
        }

        $translation = $manager->getTranslation($newKey);

        $this->assertEquals($newKey, $translation['key']);
        $this->assertEquals('admin', $translation['module']);
        $this->assertEquals([
            'en' => 'English',
            'de' => 'Deutsch'
        ], $translation['translations']);
    }

    protected function getForm()
    {
        return new Setup_Form_Translation();
    }
}
