export interface Warehouse {
  id: number
  code: string
  name: string
  address: string
  site_id: number
  locations_count?: number
  created_at: string
  updated_at: string
}

export interface WarehouseFormData {
  code: string
  name: string
  address: string
}

export interface WarehouseListProps {
  site: {
    id: number
    name: string
    slug: string
  }
  warehouses: {
    data: Warehouse[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: Array<{
      url: string | null
      label: string
      active: boolean
    }>
  }
}

export interface WarehouseCreateProps {
  site: {
    id: number
    name: string
    slug: string
  }
  suggestedCode: string
}

export interface WarehouseEditProps {
  site: {
    id: number
    name: string
    slug: string
  }
  warehouse: Warehouse
}

export interface WarehouseShowProps extends WarehouseEditProps {}
