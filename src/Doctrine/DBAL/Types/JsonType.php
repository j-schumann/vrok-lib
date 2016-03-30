<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\DBAL\Types;

use Doctrine\DBAL\Types\JsonArrayType as BaseArrayType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Extends the base class to use the varchar declaration instead of the clob declaration
 * if possible so we can use the column as/in keys. This is necessary as Doctrine does not
 * allow to set the index length even for BLOB/CLOB columns.
 *
 * @link http://www.doctrine-project.org/jira/browse/DDC-2802
 */
class JsonType extends BaseArrayType
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'json_data';
    }
}
