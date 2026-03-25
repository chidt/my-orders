export interface CustomerTypeOption {
  value: number
  label: string
}

export interface CustomerAddress {
  id?: number
  address: string
  ward_id: number | null
  province_id?: number | null
  is_default?: boolean
}

export interface Customer {
  id: number
  name: string
  phone: string
  email?: string | null
  type: number
  description?: string | null
  site_id: number
  address?: string | null
  ward_id?: number | null
  province_id?: number | null
  addresses?: CustomerAddress[]
  can_delete?: boolean
  orders_count?: number
  created_at: string
  updated_at: string
}

export interface CustomerListProps {
  site: {
    id: number
    name: string
    slug: string
  }
  customers: {
    data: Customer[]
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
  statistics: {
    total: number
    individual: number
    business: number
  }
  filters: {
    search?: string | null
    type?: string | null
    sort_by?: string | null
    sort_direction?: string | null
  }
  customerTypes: Record<string, string>
}

export interface CustomerFormProps {
  site: {
    id: number
    name: string
    slug: string
  }
  customerTypes: Record<string, string>
  provinces: Array<{ id: number; name: string }>
  customer?: Customer
}
