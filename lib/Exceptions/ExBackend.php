<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Exceptions;

use OCP\DB\Exception as NcException;

class ExBackend extends ApiException 
{
    public function __construct(string $message, Array $data = [], ?\Exception $previous = null) 
    {

        if ($previous instanceof NcException)
        {
            $reason = $previous->getReason();
            if ($reason == NcException::REASON_CONNECTION_LOST)
            {
                $data = array_merge($data, [ "db-reason" => "nextcloud lost connection to db" ]);
            }
            else if ($reason == NcException::REASON_DATABASE_OBJECT_NOT_FOUND)
            {
                $data = array_merge($data, [ "db-reason" => "table doesn't exist" ]);
            }
            else if ($reason == NcException::REASON_DEADLOCK)
            {
                return 
                (
                    $reason === NcException::REASON_CONSTRAINT_VIOLATION ||
                    $reason === NcException::REASON_FOREIGN_KEY_VIOLATION || 
                    $reason === NcException::REASON_UNIQUE_CONSTRAINT_VIOLATION
                );
                $data = array_merge($data, [ "db-reason" => "db ran into deadlock" ]);
            }
            else if ($reason == NcException::REASON_DRIVER)
            {
                $data = array_merge($data, [ "db-reason" => "db driver error occured" ]);
            }
            else if ($reason == NcException::REASON_LOCK_WAIT_TIMEOUT)
            {
                $data = array_merge($data, [ "db-reason" => "lock timeout exceeded" ]);
            }
            else if ($reason == NcException::REASON_SERVER)
            {
                $data = array_merge($data, [ "db-reason" => "generic server error" ]);
            }
            else
            {
                $data = array_merge($data, [ "db-reason" => "syntax error" ]);
            }
        }

        parent::__construct(502, $message, $data, $previous);
    }

    /**
     * This method checks if the specified Exception is a constraint error in DBAL.
     *
     * @param \Exception $ex
     * @return bool TRUE, if constraint error, FALSE if any other exception.
     * 
     */
    public static function isConstraintError(\Exception $ex): bool
    {
        if ($ex instanceof NcException)
        {
            $reason = $ex->getReason();
            return 
            (
                $reason === NcException::REASON_CONSTRAINT_VIOLATION ||
                $reason === NcException::REASON_FOREIGN_KEY_VIOLATION || 
                $reason === NcException::REASON_UNIQUE_CONSTRAINT_VIOLATION
            );
        }
        return false;
    }

}