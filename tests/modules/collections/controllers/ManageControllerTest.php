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
 * @package     Tests
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */
class Collections_ManageControllerTest extends ControllerTestCase {

    private $requestData = array();

    public function setUp() {
        parent::setUp();

        $this->requestData = array(
                    'role' => 'Collections',
                    'key' => ('foo ' . rand()),
                    'title' => ('title ' . rand()),
        );

        /* Creating first collection to work with. */
        $this->request
                ->setMethod('POST')
                ->setPost($this->requestData);
        $this->dispatch('/collections/manage/add');

        $this->assertResponseCode(200);
        $this->assertController('manage');
        $this->assertAction('add');

    }

    /**
     * Simple test action to check "add" module.
     */
    public function testAddAction() {

        // First request has been issued in setUp.
        $body = $this->getResponse()->getBody();
        $this->checkForBadStringsInHtml($body);
        $this->assertContains('SUCCESS', $body);

    }

    /**
     * Test action to check "add" module, expect failure at second insert.
     */
    public function testAddDoubleInsertAction() {

        // First request has been issued in setUp.
        // Second insert with same key should fail.
        $this->request
                ->setMethod('POST')
                ->setPost($this->requestData);
        $this->dispatch('/collections/manage/add');
        $this->assertResponseCode(400);

    }

    /**
     * Test action to check "add" module, expect failure at second insert.
     */
    public function testChangeTitleAction() {

        // First request has been issued in setUp.
        // Now changing existing collection.
        $this->requestData['title'] = 'another title';
        $this->request
                ->setMethod('POST')
                ->setPost($this->requestData);
        $this->dispatch('/collections/manage/change-title');

        $this->assertResponseCode(200);
        $this->assertController('manage');
        $this->assertAction('change-title');

        $body = $this->getResponse()->getBody();
        $this->checkForBadStringsInHtml($body);
        $this->assertContains('SUCCESS', $body);
    }

}

?>
