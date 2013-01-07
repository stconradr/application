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
 * @author      Edouard Simon (edouard.simon@zib.de)
 * @copyright   Copyright (c) 2008-2012, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */
/**
 * 
 */

if(basename(__FILE__) !== basename($argv[0])) {
    echo "script must be executed directy (not via opus-console)\n";
    exit;
}

require_once dirname(__FILE__) . '/../common/bootstrap.php';

if ($argc < 3) {
    echo "Usage: {$argv[0]} <document type> <thesis publisher ID> (dryrun)\n";
    exit;
}

$documentType = $argv[1];
$thesisPublisherId = $argv[2];
$dryrun = (isset($argv[3]) && $argv[3] == 'dryrun');

try {
    $dnbInstitute = new Opus_DnbInstitute($thesisPublisherId);
} catch (Opus_Model_NotFoundException $omnfe) {
    _log("Opus_DnbInstitute with ID <$thesisPublisherId> does not exist.\nExiting...");
    exit;
}
if($dryrun)
    _log("TEST RUN: NO DATA WILL BE MODIFIED");

$docFinder = new Opus_DocumentFinder();
$docIds = $docFinder
        ->setServerState('published')
        ->setType($documentType)->ids();

_log(count($docIds) . " documents of type '{$documentType}' found");

foreach ($docIds as $docId) {
    try {
        $doc = new Opus_Document($docId);
        $thesisPublisher = $doc->getThesisPublisher();
        if (empty($thesisPublisher)) {
            if(!$dryrun) {
                $doc->setThesisPublisher($dnbInstitute);
                $doc->store();
            }
        _log("Setting ThesisPublisher <$thesisPublisherId> on Document <$docId>");
        } else {
            $existingThesisPublisherId = $thesisPublisher[0]->getId();
            _log("ThesisPublisher <{$existingThesisPublisherId[1]}> already set for Document <$docId>");
        }
    } catch (Exception $exc) {
        _log("Error processing Document with ID $docId!");
        _log($exc->getMessage());
    }
}

function _log($message) {
    echo "$message\n";
}

?>
