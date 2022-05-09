/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormTokenField } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { getEntitiesInfo, mapToIHasNameAndId } from '../../utils';

function useIsPostTypeHierarchical( postType ) {
	return useSelect(
		( select ) => {
			const type = select( coreStore ).getPostType( postType );
			return type?.viewable && type?.hierarchical;
		},
		[ postType ]
	);
}

const EMPTY_ARRAY = [];
const SUGGESTIONS_QUERY = {
	per_page: -1,
	order: 'asc',
	orderby: 'title',
	_fields: 'id,title',
	context: 'view',
};

function ParentControl( { parents, postType, onChange } ) {
	const isHierarchical = useIsPostTypeHierarchical( postType );
	const [ search, setSearch ] = useState( '' );
	const [ value, setValue ] = useState( EMPTY_ARRAY );
	const [ suggestions, setSuggestions ] = useState( EMPTY_ARRAY );
	const debouncedSearch = useDebounce( setSearch, 250 );
	const { searchResults, searchHasResolved } = useSelect(
		( select ) => {
			if ( ! search ) {
				return { searchResults: EMPTY_ARRAY, searchHasResolved: true };
			}
			const { getEntityRecords, hasFinishedResolution } = select(
				coreStore
			);
			const selectorArgs = [
				'postType',
				postType,
				{
					...SUGGESTIONS_QUERY,
					search,
					exclude: parents,
				},
			];
			return {
				searchResults: getEntityRecords( ...selectorArgs ),
				searchHasResolved: hasFinishedResolution(
					'getEntityRecords',
					selectorArgs
				),
			};
		},
		[ search, parents ]
	);
	const currentParents = useSelect(
		( select ) => {
			if ( ! parents?.length ) return EMPTY_ARRAY;
			const { getEntityRecords } = select( coreStore );
			return getEntityRecords( 'postType', postType, {
				...SUGGESTIONS_QUERY,
				include: parents,
			} );
		},
		[ parents ]
	);
	// Update the `value` state only after the selectors are resolved
	// to avoid emptying the input when we're changing parents.
	useEffect( () => {
		if ( ! parents?.length ) {
			setValue( EMPTY_ARRAY );
		}
		if ( ! currentParents?.length ) return;
		const currentParentsInfo = getEntitiesInfo(
			mapToIHasNameAndId( currentParents, 'title.rendered' )
		);
		// Returns only the existing entity ids. This prevents the component
		// from crashing in the editor, when non existing ids are provided.
		const sanitizedValue = parents.reduce( ( accumulator, id ) => {
			const entity = currentParentsInfo.mapById[ id ];
			if ( entity ) {
				accumulator.push( {
					id,
					value: entity.name,
				} );
			}
			return accumulator;
		}, [] );
		setValue( sanitizedValue );
	}, [ parents, currentParents ] );

	const entitiesInfo = useMemo( () => {
		if ( ! searchResults?.length ) return EMPTY_ARRAY;
		return getEntitiesInfo(
			mapToIHasNameAndId( searchResults, 'title.rendered' )
		);
	}, [ searchResults ] );
	// Update suggestions only when the query has resolved.
	useEffect( () => {
		if ( ! searchHasResolved ) return;
		setSuggestions( entitiesInfo.names );
	}, [ entitiesInfo.names?.join(), searchHasResolved ] );

	// Parent control is only needed for hierarchical post types.
	if ( ! isHierarchical ) {
		return null;
	}

	const getIdByValue = ( entitiesMappedByName, entity ) => {
		const id = entity?.id || entitiesMappedByName?.[ entity ]?.id;
		if ( id ) return id;
	};
	const onParentChange = ( newValue ) => {
		const ids = Array.from(
			newValue.reduce( ( accumulator, entity ) => {
				// Verify that new values point to existing entities.
				const id = getIdByValue( entitiesInfo.mapByName, entity );
				if ( id ) accumulator.add( id );
				return accumulator;
			}, new Set() )
		);
		setSuggestions( EMPTY_ARRAY );
		onChange( { parents: ids } );
	};
	return (
		<FormTokenField
			label={ __( 'Parents' ) }
			value={ value }
			onInputChange={ debouncedSearch }
			suggestions={ suggestions }
			onChange={ onParentChange }
		/>
	);
}

export default ParentControl;
