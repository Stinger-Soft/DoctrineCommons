<?php

namespace StingerSoft\DoctrineCommons\Utils;

use StingerSoft\DoctrineCommons\Utils\Helper\JsonCountListener;
use StingerSoft\DoctrineCommons\Utils\Helper\ImportListener;

class JsonStreamImporter extends JsonImporter {
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\ImporterService::import()
	 */
	public function import($filename) {
		$stream = fopen($filename, 'r');
		try {
			if($this->output) {
				$this->output->writeln('Scanning json file...');
			}
			$countListener = new JsonCountListener();
			$parser = new \JsonStreamingParser\Parser($stream, $countListener, "\n", true, 81920);
			$parser->parse();
			rewind($stream);
			if($this->output) {
				$this->output->writeln('Scan OK! Starting import...');
			}
			$this->connection->beginTransaction();
			$this->before();
			$importListener = new ImportListener($this, $this->schemaManager, $this->connection, $countListener->getEntryCount(), $this->output);
			$parser = new \JsonStreamingParser\Parser($stream, $importListener, "\n", true, 81920);
			$parser->parse();
			$this->after();
			$this->connection->commit();
		} catch(Exception $e) {
			throw $e;
		} finally {
			fclose($stream);
		}
	}
}