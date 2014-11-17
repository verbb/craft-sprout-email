<?php
namespace Craft;

class SproutEmail_EntryElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Sprout Entry');
	}

	/**
	 * Returns whether this element type has content.
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * Returns whether this element type has titles.
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * Returns whether this element type stores data on a per-locale basis.
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return false;
	}

	/**
	 * @inheritDoc IElementType::hasStatuses()
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return true;
	}

	public function getStatuses()
	{
		return array(
			SproutEmail_EntryModel::READY     => Craft::t('Ready'),
			SproutEmail_EntryModel::PENDING   => Craft::t('Pending'),
			SproutEmail_EntryModel::DISABLED  => Craft::t('Disabled'),
			SproutEmail_EntryModel::ARCHIVED  => Craft::t('Archived'),
		);
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		// Grab all of our Notifications
		$notifications = craft()->sproutEmail_campaign->getCampaigns('notification');
		$notificationIds = array();

		// Create a list of Notification IDs we can use as criteria to filter by
		foreach ($notifications as $notification)
		{
			$notificationIds[] = $notification->id;
		}

		// Start with an option for everything
		$sources = array(
			'*' => array(
				'label'    => Craft::t('All Emails'),
			),
			'notifications' => array(
				'label' => Craft::t('Notifications'),
				'criteria' => array(
					'campaignId' => $notificationIds
				)
			)
		);

		// Prepare the data for our sources sidebar
		$campaigns = craft()->sproutEmail_campaign->getCampaigns('email');

		if (count($campaigns)) 
		{
			$sources[] = array('heading' => 'Campaigns');
		}

		foreach ($campaigns as $campaign) 
		{	
			$key = 'campaign:'.$campaign->id;
			
			$sources[$key] = array(
				'label' => $campaign->name,
				'data' => array('campaignId' => $campaign->id),
				'criteria' => array('campaignId' => $campaign->id)
			);
		}

		return $sources;
	}

	public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context)
	{
		if ($context == 'index')
		{
			$criteria->offset = 0;
			$criteria->limit = null;

			$source = $this->getSource($sourceKey, $context);

			return craft()->templates->render('sproutemail/entries/_entryindex', array(
				'context'             => $context,
				'elementType'         => new ElementTypeVariable($this),
				'disabledElementIds'  => $disabledElementIds,
				'elements'            => $criteria->find(),
			));
		}
		else
		{
			return parent::getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context);
		}
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'title'        => Craft::t('Title'),
			'dateCreated'  => Craft::t('Date Created'),
			'dateUpdated'  => Craft::t('Date Updated'),
		);
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'title' => AttributeType::String,
			'subjectLine' => AttributeType::String,
			'campaignId' => AttributeType::Number,
		);
	}

	/**
	 * @inheritDoc IElementType::getElementQueryStatusCondition()
	 *
	 * @param DbCommand $query
	 * @param string    $status
	 *
	 * @return array|false|string|void
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status)
	{
		switch ($status)
		{
			case SproutEmail_EntryModel::DISABLED:
			{
				return 'campaigns.template IS NULL';
			}

			case SproutEmail_EntryModel::PENDING:
			{
				return array('and',
					'elements.enabled = 0',
					'campaigns.template IS NOT NULL',
					'entries.sent = 0',
				);
			}

			case SproutEmail_EntryModel::READY:
			{
				return array('and',
					'elements.enabled = 1',
					'elements_i18n.enabled = 1',
					'campaigns.template IS NOT NULL',
					'entries.sent = 0',
				);
			}

			case SproutEmail_EntryModel::ARCHIVED:
			{
				return 'entries.sent = 1';
			}
		}
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array(
			// 'entryId', 
			'title',
		);
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('entries.id AS entryId, 
									 entries.campaignId AS campaignId,
									 entries.subjectLine AS subjectLine, 
									 entries.sent AS sent,
									 campaigns.type AS type,
				')
			->join('sproutemail_entries entries', 'entries.id = elements.id')
			->join('sproutemail_campaigns campaigns', 'campaigns.id = entries.campaignId');

		if ($criteria->campaignId) 
		{
			$query->andWhere(DbHelper::parseParam('entries.campaignId', $criteria->campaignId, $query->params));
		}
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return SproutEmail_EntryModel::populateModel($row);
	}
}