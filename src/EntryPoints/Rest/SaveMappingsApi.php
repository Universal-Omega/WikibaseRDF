<?php

declare( strict_types = 1 );

namespace ProfessionalWiki\WikibaseRDF\EntryPoints\Rest;

use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Validator\BodyValidator;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use ProfessionalWiki\WikibaseRDF\MappingListSerializer;
use ProfessionalWiki\WikibaseRDF\WikibaseRdfExtension;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikimedia\ParamValidator\ParamValidator;

class SaveMappingsApi extends SimpleHandler {

	public function __construct(
		private EntityIdParser $entityIdParser,
		private MappingListSerializer $mappingListSerializer
	) {
	}

	public function run( string $entityId ): Response {
		$body = $this->getRequest()->getBody()->getContents();
		$mappings = $this->mappingListSerializer->fromJson( $body );

		$presenter = WikibaseRdfExtension::getInstance()->newRestSaveMappingsPresenter( $this->getResponseFactory() );
		$useCase = WikibaseRdfExtension::getInstance()->newSaveMappingsUseCase( $presenter );
		$useCase->saveMappings( $this->getEntityId( $entityId ), $mappings );

		return $presenter->getResponse();
	}

	/**
	 * @inheritDoc
	 * @return array<string, array<string, mixed>>
	 */
	public function getParamSettings(): array {
		return [
			'entity_id' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		if ( $contentType !== 'application/json' ) {
			throw new HttpException(
				"Unsupported Content-Type",
				415,
				[ 'content_type' => $contentType ]
			);
		}

		// TODO: Can we validate the Mapping fields here?
		return new JsonBodyValidator( [] );
	}

	private function getEntityId( string $entityId ): EntityId {
		return $this->entityIdParser->parse( $entityId );
	}

}
