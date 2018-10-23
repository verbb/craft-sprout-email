<?php

namespace Craft;

/**
 * Class SproutEmail_CampaignEmailElementType
 */
class SproutEmail_CampaignEmailElementType extends BaseElementType
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Sprout Email Campaign Emails');
	}

	/**
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
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

	/**
	 * @return array
	 */
	public function getStatuses()
	{
		$statuses[SproutEmail_CampaignEmailModel::SENT] = Craft::t('Sent');

		if (sproutEmail()->getConfig('displayDateScheduled', false))
		{
			$statuses[SproutEmail_CampaignEmailModel::SCHEDULED] = Craft::t('Scheduled');
		}

		$statuses[SproutEmail_CampaignEmailModel::PENDING]  = Craft::t('Pending');
		$statuses[SproutEmail_CampaignEmailModel::DISABLED] = Craft::t('Disabled');

		return $statuses;
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = array(
			'*' => array(
				'label' => Craft::t('All Campaigns'),
			),
		);

		$campaignTypes = sproutEmail()->campaignTypes->getCampaignTypes();

		if (count($campaignTypes))
		{
			$sources[] = array('heading' => Craft::t('Campaigns'));

			foreach ($campaignTypes as $campaignType)
			{
				$key = 'campaignType:' . $campaignType->id;

				$sources[$key] = array(
					'label'    => $campaignType->name,
					'data'     => array('campaignTypeId' => $campaignType->id),
					'criteria' => array('campaignTypeId' => $campaignType->id)
				);
			}
		}

		return $sources;
	}

	/**
	 * @param ElementCriteriaModel $criteria
	 * @param array                $disabledElementIds
	 * @param array                $viewState
	 * @param null|string          $sourceKey
	 * @param null|string          $context
	 * @param                      $includeContainer
	 * @param                      $showCheckboxes
	 *
	 * @return string
	 */
	public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes)
	{
		craft()->templates->includeJsResource('sproutemail/js/sproutmodal.js');
		craft()->templates->includeJs('var sproutModalInstance = new SproutModal(); sproutModalInstance.init();');

		sproutEmail()->mailers->includeMailerModalResources();

		$order = isset($viewState['order']) ? $viewState['order'] : 'dateCreated';
		$sort  = isset($viewState['sort']) ? $viewState['sort'] : 'desc';

		$criteria->limit = null;
		$criteria->order = sprintf('%s %s', $order, $sort);

		return parent::getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);
	}

	/**
	 * @inheritDoc IElementType::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($element->campaignTypeId);

		$passHtml = '<span class="success" title="' . Craft::t('Passed') . '" data-icon="check"></span>';
		$failHtml = '<span class="error" title="' . Craft::t('Failed') . '" data-icon="error"></span>';

		if ($attribute === 'send')
		{
			$mailer = sproutEmail()->mailers->getMailerByName($campaignType->mailer);

			return craft()->templates->render('sproutemail/_partials/campaigns/prepareLink', array(
				'campaignEmail' => $element,
				'campaignType'  => $campaignType,
				'mailer'        => $mailer
			));
		}

		if ($attribute === 'preview')
		{
			return craft()->templates->render('sproutemail/_partials/campaigns/previewLinks', array(
				'email'        => $element,
				'campaignType' => $campaignType,
				'type'         => 'html'
			));
		}

		if ($attribute === 'template')
		{
			return '<code>' . $element->template . '</code>';
		}

		if ($attribute === 'contentCheck')
		{
			return $element->isContentReady() ? $passHtml : $failHtml;
		}

		if ($attribute === 'recipientsCheck')
		{
			return $element->isListReady() ? $passHtml : $failHtml;
		}

		if ($attribute === 'dateScheduled' && $element->dateScheduled)
		{
			return '<span title="' . $element->dateScheduled->format('l, d F Y, h:ia') . '">' . $element->dateScheduled->uiTimestamp() . '</span>';
		}

		if ($attribute === 'dateSent' && $element->dateSent)
		{
			return '<span title="' . $element->dateSent->format('l, d F Y, h:ia') . '">' . $element->dateSent->uiTimestamp() . '</span>';
		}

		return parent::getTableAttributeHtml($element, $attribute);
	}

	/**
	 * @return array
	 */
	public function defineAvailableTableAttributes()
	{
		$attributes['title'] = array('label' => Craft::t('Subject'));

		if (sproutEmail()->getConfig('displayDateScheduled', false))
		{
			$attributes['dateScheduled'] = array('label' => Craft::t('Date Scheduled'));
		}

		$attributes['dateSent']        = array('label' => Craft::t('Date Sent'));
		$attributes['contentCheck']    = array('label' => Craft::t('Content'));
		$attributes['recipientsCheck'] = array('label' => Craft::t('Recipients'));
		$attributes['dateCreated']     = array('label' => Craft::t('Date Created'));
		$attributes['dateUpdated']     = array('label' => Craft::t('Date Updated'));
		$attributes['template']        = array('label' => Craft::t('Template'));
		$attributes['send']            = array('label' => Craft::t('Send'));
		$attributes['preview']         = array('label' => Craft::t('Preview'), 'icon' => 'view');
		$attributes['link']            = array('label' => Craft::t('Link'), 'icon' => 'world');

		return $attributes;
	}

	/**
	 * @param null $source
	 *
	 * @return array
	 */
	public function getDefaultTableAttributes($source = null)
	{
		$attributes = array();

		$attributes[] = 'title';
		$attributes[] = 'contentCheck';
		$attributes[] = 'recipientsCheck';
		$attributes[] = 'dateCreated';
		$attributes[] = 'dateSent';
		$attributes[] = 'send';
		$attributes[] = 'preview';
		$attributes[] = 'link';

		return $attributes;
	}

	/**
	 * @return mixed
	 */
	public function defineSortableAttributes()
	{
		$attributes['title'] = Craft::t('Subject');

		if (sproutEmail()->getConfig('displayDateScheduled', false))
		{
			$attributes['dateScheduled'] = Craft::t('Date Scheduled');
		}

		$attributes['dateSent']    = Craft::t('Date Sent');
		$attributes['dateCreated'] = Craft::t('Date Created');
		$attributes['dateUpdated'] = Craft::t('Date Updated');

		return $attributes;
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'title'          => AttributeType::String,
			'campaignTypeId' => AttributeType::Number,
			'campaignHandle' => AttributeType::Handle,
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
			case SproutEmail_CampaignEmailModel::ENABLED:
			{
				$query->andWhere('elements.enabled = 1');

				break;
			}
			case SproutEmail_CampaignEmailModel::DISABLED:
			{
				$query->andWhere('elements.enabled = 0');

				break;
			}
			case SproutEmail_CampaignEmailModel::PENDING:
			{
				$query->andWhere('elements.enabled = 1');
				$query->andWhere('campaigns.dateSent IS NULL');
				$query->andWhere('campaigns.dateScheduled IS NULL');

				break;
			}
			case SproutEmail_CampaignEmailModel::SCHEDULED:
			{
				$query->andWhere('elements.enabled = 1');
				$query->andWhere('campaigns.dateSent IS NULL');
				$query->andWhere('campaigns.dateScheduled IS NOT NULL');

				break;
			}
			case SproutEmail_CampaignEmailModel::SENT:
			{
				$query->andWhere('elements.enabled = 1');
				$query->andWhere('campaigns.dateSent IS NOT NULL');

				break;
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
		return array('title');
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect(
				'campaigns.id, 
				 campaigns.subjectLine as subjectLine,
				 campaigns.campaignTypeId as campaignTypeId,
				 campaigns.recipients as recipients,
				 campaigns.fromName as fromName,
				 campaigns.fromEmail as fromEmail,
				 campaigns.replyToEmail as replyToEmail,
				 campaigns.dateSent as dateSent,
				 campaigns.dateScheduled as dateScheduled,
				 campaigns.emailSettings as emailSettings,
				 campaigns.listSettings as listSettings,
				 campaigns.enableFileAttachments as enableFileAttachments,
				 campaigntype.template as template'
			)
			->join('sproutemail_campaignemails campaigns', 'campaigns.id = elements.id')
			->join('sproutemail_campaigntype campaigntype', 'campaigntype.id = campaigns.campaignTypeId');

		if ($criteria->campaignTypeId)
		{
			$query->andWhere(DbHelper::parseParam('campaigns.campaignTypeId', $criteria->campaignTypeId, $query->params));
		}

		if ($criteria->campaignHandle)
		{
			$query->andWhere(DbHelper::parseParam('campaigntype.handle', $criteria->campaignHandle, $query->params));
		}
	}

	/**
	 * Gives us the ability to render campaign previews by using the Craft API and templates/render
	 *
	 * @param BaseElementModel $element
	 *
	 * @return array|bool
	 * @throws HttpException
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element)
	{
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($element->campaignTypeId);

		if (!$campaignType)
		{
			return false;
		}

		$extension = null;

		if (($type = craft()->request->getQuery('type')))
		{
			$extension = in_array(strtolower($type), array('txt', 'text')) ? '.txt' : null;
		}

		if (!craft()->templates->doesTemplateExist($campaignType->template . $extension))
		{
			$templateName = $campaignType->template . $extension;

			sproutEmail()->error(Craft::t("The template '{templateName}' could not be found", array(
				'templateName' => $templateName
			)));
		}

		return array(
			'action' => 'templates/render',
			'params' => array(
				'template'  => $campaignType->template . $extension,
				'variables' => array(
					'email'        => $element,
					'campaignType' => $campaignType
				)
			)
		);
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		$setStatusAction              = craft()->elements->getAction('SetStatus');
		$setStatusAction->onSetStatus = function (Event $event)
		{
			if ($event->params['status'] === BaseElementModel::ENABLED)
			{
				// Update Date Updated value as well
				craft()->db->createCommand()->update(
					'sproutemail_campaignemails',
					array('dateUpdated' => DateTimeHelper::currentTimeForDb()),
					array('and', array('in', 'id', $event->params['elementIds']))
				);
			}
		};

		$markSentAction   = craft()->elements->getAction('SproutEmail_MarkSent');
		$markUnsentAction = craft()->elements->getAction('SproutEmail_MarkUnsent');
		$deleteAction     = craft()->elements->getAction('SproutEmail_CampaignEmailDelete');

		return array($setStatusAction, $markSentAction, $markUnsentAction, $deleteAction);
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 *
	 * @return BaseModel
	 */
	public function populateElementModel($row)
	{
		return SproutEmail_CampaignEmailModel::populateModel($row);
	}
}