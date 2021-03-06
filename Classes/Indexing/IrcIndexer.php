<?php
namespace FluidTYPO3\Fluidtypo3org\Indexing;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class IrcIndexer extends \Tx_Solr_IndexQueue_Indexer {

	/**
	 * Indexes an item from the indexing queue.
	 *
	 * @param \Tx_Solr_IndexQueue_Item $item
	 * @return \Apache_Solr_Response
	 */
	public function index(\Tx_Solr_IndexQueue_Item $item) {
		$item->setChanged(time());
		$record = $item->getRecord();
		$solrConnections = $this->getSolrConnectionsByItem($item);
		foreach ($solrConnections as $systemLanguageUid => $solrConnection) {
			$placeholder = $item->getRecord();
			$filename = $placeholder['filename'];
			$basename = pathinfo($filename, PATHINFO_FILENAME);
			list (, $date) = explode('_', $basename);
			$lines = file($filename);
			$sorted = array();
			foreach ($lines as $index => $line) {
				if (9 === strpos($line, '] *** ') || 9 === strpos($line, '] -') || FALSE !== strpos($line, '<FluidTYPO3>')) {
					unset($lines[$index]);
				} else {
					$mark = substr($line, 1, 8);
					$sorted[$mark] = htmlentities(substr($line, 11));
				}
			}
			$sorted = array_map('trim', $sorted);
			foreach ($sorted as $mark => $line) {
				$id = \Tx_Solr_Util::getDocumentId(
					$item->getType(),
					$record['pid'],
					$record['uid'] . $mark
				);
				$url = $this->createHashedShortcutUrl('community/irc-logs.html', 'tx_fluidtypo3org_content[date]=' . $date, $mark);
				$document = $this->getBaseDocument($item, $item->getRecord());
				$document->setField('content', $line);
				$document->setField('title', 'IRC Log entry ' . $date . ' ' . $mark);
				$document->setField('url', $url);
				$document->setField('id', $id);
				$solrConnection->addDocument($document);
			}
		}

		return TRUE;
	}

	/**
	 * @param string $basePage
	 * @param string $parameters
	 * @param string $hash
	 * @return string
	 */
	protected function createHashedShortcutUrl($basePage, $parameters, $hash) {
		/** @var $cacheHash \TYPO3\CMS\Frontend\Page\CacheHashCalculator */
		$cacheHash = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');
		$cHash = $cacheHash->generateForParameters($parameters);
		$url = $basePage . '?' . $parameters . '&cHash=' . $cHash . '#' . $hash;
		return $url;
	}

}
