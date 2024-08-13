<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000000Date20240101000000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		// create cabinet table
		if (!$schema->hasTable("aktnschrnk_cabinets"))
		{

			$table = $schema->createTable("aktnschrnk_cabinets");
			$table->addColumn("id", "bigint", [
				"autoincrement" => true,
				"notnull" => true,
				"unsigned" => true,
			]);
			$table->addColumn("mount_type", "smallint", [
				"notnull" => true,
			]);
			$table->addColumn("mount_path", "string", [
				"notnull" => false,
			]);
			$table->addColumn("mount_gf_id", "bigint", [
				"notnull" => false,
			]);

			$table->setPrimaryKey(["id"]);
			$table->addUniqueIndex(['mount_type', 'mount_gf_id'], 'uniqaktn_cabinet_gf');
			$table->addUniqueIndex(['mount_type', 'mount_path'], 'uniqaktn_cabinet_path');

		}

		return $schema;

		/*

		// create access table
		if (!$schema->hasTable('aktnschrnk_access'))
		{

			$table = $schema->createTable('aktnschrnk_access');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('cabinet_path', 'string', [
				'notnull' => true
			]);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['cabinet_path'], 'uniq_cabinet');
			
		}

		// create files table
		if (!$schema->hasTable('aktnschrnk_docfiles'))
		{

			$table = $schema->createTable('aktnschrnk_docfiles');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('file_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('doc_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('file_pos', 'smallint', [
				'notnull' => true,
				'unsigned' => true,
				'default' => 9999,
			]);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['file_id','doc_id'], 'aktnschr_uniq_docfile');
			
		}

		// create documents table
		if (!$schema->hasTable('aktnschrnk_docs'))
		{

			$table = $schema->createTable('aktnschrnk_docs');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('access_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('title', 'string', [
				'notnull' => true
			]);
			$table->addColumn('time_mentioned', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('time_added', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('time_modified', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('time_expires', 'bigint', [
				'notnull' => false,
				'unsigned' => true,
				'default' => null,
			]);
			$table->addColumn('recipient_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('origin_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('contact_id', 'smallint', [
				'notnull' => false,
				'unsigned' => true,
				'default' => null,
			]);
			$table->addColumn('tray1', 'string', [
				'notnull' => true,
			]);
			$table->addColumn('tray2', 'string', [
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('tray3', 'string', [
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('group_by', 'string', [
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('tags', 'json', [
				'notnull' => false,
				'default' => '[]',
			]);
			$table->addColumn('description', 'string', [
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('path_template', 'string', [
				'notnull' => true
			]);
			$table->addColumn('path_value', 'string', [
				'notnull' => true
			]);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['access_id', 'title', 'time_mentioned', 'recipient_id', 'origin_id', 'tray1', 'tray2', 'tray3', 'group_by', 'path_template'], 'aktnschr_uniq_doc');
			
		}

		// create tasks table 
		if (!$schema->hasTable('aktnschrnk_tasks'))
		{

			$table = $schema->createTable('aktnschrnk_tasks');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('access_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true
			]);
			$table->addColumn('description', 'string', [
				'notnull' => false,
				'default' => ''
			]);
			$table->addColumn('time_from', 'bigint', [
				'notnull' => false,
				'unsigned' => true,
				'default' => null,
			]);
			$table->addColumn('time_until', 'bigint', [
				'notnull' => false,
				'unsigned' => true,
				'default' => null,
			]);
			$table->addColumn('data', 'json');
			
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['access_id', 'name'], 'aktnschr_uniq_task');

		}

		// create references table
		if (!$schema->hasTable('aktnschrnk_references'))
		{

			$table = $schema->createTable('aktnschrnk_references');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('src_type', 'smallint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('src_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('ref_type', 'smallint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('ref_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);

			$table->setPrimaryKey(['id']);
			
		}

		// create recipients table
		if (!$schema->hasTable('aktnschrnk_recipients'))
		{

			$table = $schema->createTable('aktnschrnk_recipients');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('access_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true
			]);
			$table->addColumn('group', 'string', [
				'notnull' => true
			]);
			
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['access_id','name','group'], 'aktnschrnk_uniq_recipient');
			
		}

		// create origins table
		if (!$schema->hasTable('aktnschrnk_origins'))
		{

			$table = $schema->createTable('aktnschrnk_origins');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('access_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true
			]);
			$table->addColumn('address', 'string', [
				'notnull' => true
			]);
			$table->addColumn('contacts', 'json', [
				'notnull' => true
			]);
			
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['access_id','name'], 'aktnschrnk_uniq_origin');
			

		}

		// create cascaded tray table
		if (!$schema->hasTable('aktnschrnk_formerval'))
		{

			$table = $schema->createTable('aktnschrnk_formerval');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('access_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('recipient_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('origin_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('contact_id', 'smallint', [
				'notnull' => false,
				'unsigned' => true,
				'default' => null
			]);
			$table->addColumn('tray1', 'string', [
				'notnull' => true
			]);
			$table->addColumn('tray2', 'string', [
				'notnull' => false,
				'default' => null
			]);
			$table->addColumn('tray3', 'string', [
				'notnull' => false,
				'default' => null
			]);
			$table->addColumn('group_by', 'string', [
				'notnull' => false,
				'default' => null
			]);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['access_id','recipient_id','origin_id','contact_id','tray1','tray2','tray3','group_by'], 'aktnschrnk_uniq_formerval');
			
		}

		*/

		return $schema;
	}
}
