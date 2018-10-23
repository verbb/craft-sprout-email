<?php

namespace Craft;

class SproutEmail_CampaignEmailsService extends BaseApplicationComponent
{
	protected $campaignEmailRecord;

	/**
	 * SproutEmail_CampaignEmailsService constructor.
	 *
	 * @param null $campaignEmailRecord
	 */
	public function __construct($campaignEmailRecord = null)
	{
		$this->campaignEmailRecord = $campaignEmailRecord;

		if (is_null($this->campaignEmailRecord))
		{
			$this->campaignEmailRecord = SproutEmail_CampaignEmailRecord::model();
		}
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return bool|BaseRecord|SproutEmail_CampaignEmailRecord
	 * @throws Exception
	 * @throws \Exception
	 */
	public function saveCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$isNewEntry          = true;
		$campaignEmailRecord = new SproutEmail_CampaignEmailRecord();

		if ($campaignEmail->id && !$campaignEmail->saveAsNew)
		{
			$campaignEmailRecord = SproutEmail_CampaignEmailRecord::model()->findById($campaignEmail->id);
			$isNewEntry          = false;

			if (!$campaignEmailRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $campaignEmail->id)));
			}
		}

		$campaignEmailRecord->campaignTypeId = $campaignEmail->campaignTypeId;

		if ($campaignType->titleFormat)
		{
			$renderedSubject = craft()->templates->renderObjectTemplate($campaignType->titleFormat, $campaignEmail);

			$campaignEmail->getContent()->title = $renderedSubject;
			$campaignEmail->subjectLine         = $renderedSubject;
			$campaignEmailRecord->subjectLine   = $renderedSubject;
		}
		else
		{

			$campaignEmail->getContent()->title = $campaignEmail->subjectLine;
			$campaignEmailRecord->subjectLine   = $campaignEmail->subjectLine;
		}

		$campaignEmailRecord->setAttributes($campaignEmail->getAttributes());

		$mailer = $campaignType->getMailer();

		// Give the Mailer a chance to prep the settings from post
		$preppedSettings = $mailer->prepListSettings($campaignEmail->listSettings);

		// Set the prepped settings on the FieldRecord, FieldModel, and the field type
		$campaignEmailRecord->listSettings = $preppedSettings;

		$campaignEmailRecord->validate();

		if ($campaignEmail->saveAsNew)
		{
			// Prevent subjectLine to be appended by a number
			$campaignEmailRecord->subjectLine = $campaignEmail->subjectLine;

			$campaignEmail->getContent()->title = $campaignEmail->subjectLine;
		}

		$campaignEmail->addErrors($campaignEmailRecord->getErrors());

		if (!$campaignEmail->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{

				if (craft()->elements->saveElement($campaignEmail))
				{
					// Now that we have an element ID, save it on the other stuff
					if ($isNewEntry)
					{
						$campaignEmailRecord->id = $campaignEmail->id;
					}

					$campaignEmailRecord->save(false);

					if ($transaction && $transaction->active)
					{
						$transaction->commit();
					}

					return $campaignEmailRecord;
				}
			}
			catch (\Exception $e)
			{
				if ($transaction && $transaction->active)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}

		return false;
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 *
	 * @return bool
	 * @throws \CDbException
	 * @throws \Exception
	 */
	public function deleteCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			craft()->elements->deleteElementById($campaignEmail->id);

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return true;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Return Entry by id
	 *
	 * @param int $emailId
	 *
	 * @return SproutEmail_CampaignEmailModel
	 */
	public function getCampaignEmailById($emailId)
	{
		return craft()->elements->getElementById($emailId, 'SproutEmail_CampaignEmail');
	}

	/**
	 * Returns the value of a given field
	 *
	 * @param string $field
	 * @param string $value
	 *
	 * @return SproutEmail_CampaignEmailRecord
	 */
	public function getFieldValue($field, $value)
	{
		$criteria            = new \CDbCriteria();
		$criteria->condition = "{$field} =:value";
		$criteria->params    = array(':value' => $value);
		$criteria->limit     = 1;

		$result = SproutEmail_CampaignEmailRecord::model()->find($criteria);

		return $result;
	}

	/**
	 * @param SproutEmail_CampaignTypeModel $campaignType
	 *
	 * @return bool
	 */
	public function saveRelatedCampaignEmail(SproutEmail_CampaignTypeModel $campaignType)
	{
		$defaultMailer         = sproutEmail()->mailers->getMailerByName('defaultmailer');
		$defaultMailerSettings = $defaultMailer->getSettings();

		$campaignEmail = new SproutEmail_CampaignEmailModel();

		$campaignEmail->campaignTypeId = $campaignType->getAttribute('id');
		$campaignEmail->dateCreated    = date('Y-m-d H:i:s');
		$campaignEmail->enabled        = true;
		$campaignEmail->saveAsNew      = true;
		$campaignEmail->fromName       = $defaultMailerSettings->fromName;
		$campaignEmail->fromEmail      = $defaultMailerSettings->fromEmail;
		$campaignEmail->replyToEmail   = $defaultMailerSettings->replyToEmail;
		$campaignEmail->recipients     = null;
		$campaignEmail->subjectLine    = $campaignType->getAttribute('name');

		return sproutEmail()->campaignEmails->saveCampaignEmail($campaignEmail, $campaignType);
	}

	/**
	 * Output the prepared Email to the page
	 *
	 * @param EmailModel $email
	 * @param string     $template
	 */
	public function showCampaignEmail(EmailModel $email, $fileExtension = 'html')
	{
		if ($fileExtension == 'txt')
		{
			$output = $email->body;
		}
		else
		{
			$output = $email->htmlBody;
		}

		// Output it into a buffer, in case TasksService wants to close the connection prematurely
		ob_start();

		echo $output;

		// End the request
		craft()->end();
	}

	/**
	 * Update Date Sent column every time campaign email is sent
	 *
	 * @param Event $event
	 */
	public function updateDateSent($campaignEmail)
	{
		if ($campaignEmail->id != null)
		{
			$campaignEmailRecord = SproutEmail_CampaignEmailRecord::model()->findById($campaignEmail->id);

			if ($campaignEmailRecord)
			{
				$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

				$campaignEmailRecord->dateSent = DateTimeHelper::currentTimeForDb();

				if ($campaignEmailRecord->save(false))
				{
					if ($transaction && $transaction->active)
					{
						$transaction->commit();
					}
				}
			}
		}
	}

	public function saveEmailSettings($campaignEmail, array $values = array())
	{
		if ($campaignEmail->id != null)
		{
			$campaignEmailRecord = SproutEmail_CampaignEmailRecord::model()->findById($campaignEmail->id);

			if ($campaignEmailRecord)
			{
				$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

				$campaignEmailRecord->emailSettings = $values;

				if ($campaignEmailRecord->save(false))
				{
					if ($transaction && $transaction->active)
					{
						$transaction->commit();
					}
				}
			}
		}
	}
}